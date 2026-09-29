<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Subject;
use App\Models\ClassRoom;
use Illuminate\Http\Request;

class ExamSubjectController extends Controller
{
    public function index()
    {
        $examSubjects = ExamSubject::with([
            'exam',
            'subject',
            'classRoom'
        ])
        ->orderByDesc('id')
        ->get();

        return view(
            'exam_subjects.index',
            compact('examSubjects')
        );
    }

    public function create()
    {
        $exams = Exam::orderByDesc('id')->get();

        $subjects = Subject::where('status', 'active')
            ->orderBy('name')
            ->get();

        $classes = ClassRoom::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'exam_subjects.create',
            compact(
                'exams',
                'subjects',
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

            'subject_id' => [
                'required',
                'exists:subjects,id'
            ],

            'class_id' => [
                'required',
                'exists:classes,id'
            ],

            'max_marks' => [
                'required',
                'numeric',
                'min:1'
            ],
        ]);

        $exists = ExamSubject::where([
            'exam_id' => $validated['exam_id'],
            'subject_id' => $validated['subject_id'],
            'class_id' => $validated['class_id'],
        ])->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'subject_id' =>
                        'This subject has already been added to this exam for this class.'
                ])
                ->withInput();
        }

        ExamSubject::create($validated);

        return redirect()
            ->route('exam-subjects.index')
            ->with(
                'success',
                'Subject added to exam successfully.'
            );
    }

    public function destroy(ExamSubject $examSubject)
    {
        $examSubject->delete();

        return redirect()
            ->route('exam-subjects.index')
            ->with(
                'success',
                'Subject removed from exam successfully.'
            );
    }
}