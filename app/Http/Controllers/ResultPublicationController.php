<?php

namespace App\Http\Controllers;

use App\Models\ResultPublication;
use App\Models\Exam;
use App\Models\ClassRoom;
use App\Models\Mark;
use Illuminate\Http\Request;

class ResultPublicationController extends Controller
{
    public function index()
    {
        $publications = ResultPublication::with([
            'exam',
            'classRoom',
            'publishedBy',
        ])
        ->orderByDesc('id')
        ->get();

        return view(
            'result_publications.index',
            compact('publications')
        );
    }

    public function create()
    {
        $exams = Exam::orderByDesc('id')->get();

        $classes = ClassRoom::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'result_publications.create',
            compact(
                'exams',
                'classes'
            )
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => [
                'required',
                'exists:exams,id'
            ],

            'class_id' => [
                'required',
                'exists:classes,id'
            ],

            'remarks' => [
                'nullable',
                'string'
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Check whether this result is already published
        |--------------------------------------------------------------------------
        */

        $alreadyPublished = ResultPublication::where(
            'exam_id',
            $validated['exam_id']
        )
        ->where(
            'class_id',
            $validated['class_id']
        )
        ->where(
            'status',
            'Published'
        )
        ->exists();

        if ($alreadyPublished) {

            return back()
                ->withErrors([
                    'exam_id' =>
                        'Results for this exam and class have already been published.'
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Get exam subjects for this class
        |--------------------------------------------------------------------------
        */

        $examSubjectIds = \App\Models\ExamSubject::where(
            'exam_id',
            $validated['exam_id']
        )
        ->where(
            'class_id',
            $validated['class_id']
        )
        ->pluck('id');

        if ($examSubjectIds->isEmpty()) {

            return back()
                ->withErrors([
                    'exam_id' =>
                        'No subjects have been configured for this exam and class.'
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Check marks
        |--------------------------------------------------------------------------
        */

        $marks = Mark::whereIn(
            'exam_subject_id',
            $examSubjectIds
        )->get();

        if ($marks->isEmpty()) {

            return back()
                ->withErrors([
                    'exam_id' =>
                        'No marks have been entered for this exam and class.'
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Check pending marks
        |--------------------------------------------------------------------------
        */

        $pendingMarks = $marks->where(
            'status',
            '!=',
            'Approved'
        );

        if ($pendingMarks->count() > 0) {

            return back()
                ->withErrors([
                    'exam_id' =>
                        'Results cannot be published because some marks are still pending approval.'
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Publish results
        |--------------------------------------------------------------------------
        */

        ResultPublication::create([
            'exam_id' => $validated['exam_id'],
            'class_id' => $validated['class_id'],
            'published_by' => auth()->id(),
            'published_at' => now(),
            'status' => 'Published',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('result-publications.index')
            ->with(
                'success',
                'Results published successfully.'
            );
    }
}