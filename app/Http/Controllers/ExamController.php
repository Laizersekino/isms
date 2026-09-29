<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with([
            'academicYear',
            'term'
        ])
        ->orderByDesc('id')
        ->get();

        return view('exams.index', compact('exams'));
    }

    public function create()
    {
        $academicYears = AcademicYear::orderByDesc('id')->get();
        $terms = Term::orderByDesc('id')->get();

        return view('exams.create', compact(
            'academicYears',
            'terms'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100'
            ],

            'exam_type' => [
                'required',
                'string',
                'max:50'
            ],

            'academic_year_id' => [
                'required',
                'exists:academic_years,id'
            ],

            'term_id' => [
                'required',
                'exists:terms,id'
            ],

            'start_date' => [
                'required',
                'date'
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'status' => [
                'required',
                'in:Draft,Active,Completed,Cancelled'
            ],
        ]);

        Exam::create($validated);

        return redirect()
            ->route('exams.index')
            ->with('success', 'Exam created successfully.');
    }

    public function edit(Exam $exam)
    {
        $academicYears = AcademicYear::orderByDesc('id')->get();
        $terms = Term::orderByDesc('id')->get();

        return view('exams.edit', compact(
            'exam',
            'academicYears',
            'terms'
        ));
    }

    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100'
            ],

            'exam_type' => [
                'required',
                'string',
                'max:50'
            ],

            'academic_year_id' => [
                'required',
                'exists:academic_years,id'
            ],

            'term_id' => [
                'required',
                'exists:terms,id'
            ],

            'start_date' => [
                'required',
                'date'
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'status' => [
                'required',
                'in:Draft,Active,Completed,Cancelled'
            ],
        ]);

        $exam->update($validated);

        return redirect()
            ->route('exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();

        return redirect()
            ->route('exams.index')
            ->with('success', 'Exam deleted successfully.');
    }
}