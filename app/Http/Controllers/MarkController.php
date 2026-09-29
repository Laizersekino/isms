<?php

namespace App\Http\Controllers;

use App\Models\Mark;
use App\Models\ExamSubject;
use App\Models\Student;
use Illuminate\Http\Request;

class MarkController extends Controller
{
    public function index()
    {
        $marks = Mark::with([
            'student',
            'examSubject.exam',
            'examSubject.subject',
            'examSubject.classRoom',
            'enteredBy',
            'approvedBy',
        ])
        ->orderByDesc('id')
        ->get();

        return view('marks.index', compact('marks'));
    }

    public function create(Request $request)
    {
        $examSubjects = ExamSubject::with([
            'exam',
            'subject',
            'classRoom',
        ])
        ->orderByDesc('id')
        ->get();

        $selectedExamSubject = null;
        $students = collect();

        if ($request->filled('exam_subject_id')) {

            $selectedExamSubject = $examSubjects->firstWhere(
                'id',
                $request->exam_subject_id
            );

            if (!$selectedExamSubject) {
                abort(404, 'Exam subject not found.');
            }

            $students = Student::whereHas('enrollments', function ($query) use ($selectedExamSubject) {

                $query->where(
                    'class_id',
                    $selectedExamSubject->class_id
                )
                ->where(
                    'status',
                    'active'
                );

            })
            ->orderBy('first_name')
            ->get();
        }

        return view('marks.create', compact(
            'examSubjects',
            'selectedExamSubject',
            'students'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_subject_id' => [
                'required',
                'exists:exam_subjects,id'
            ],

            'students' => [
                'required',
                'array'
            ],

            'students.*.marks_obtained' => [
                'required',
                'numeric',
                'min:0'
            ],

            'students.*.remarks' => [
                'nullable',
                'string'
            ],
        ]);

        $examSubject = ExamSubject::findOrFail(
            $validated['exam_subject_id']
        );

        foreach ($validated['students'] as $studentId => $studentData) {

            if (
                $studentData['marks_obtained']
                > $examSubject->max_marks
            ) {
                return back()
                    ->withErrors([
                        'students' =>
                            'Marks obtained cannot exceed the maximum marks of '
                            . $examSubject->max_marks
                            . '.'
                    ])
                    ->withInput();
            }

            $marksObtained = $studentData['marks_obtained'];

            $grade = $this->calculateGrade(
                $marksObtained,
                $examSubject->max_marks
            );

            Mark::updateOrCreate(
                [
                    'exam_subject_id' => $examSubject->id,
                    'student_id' => $studentId,
                ],
                [
                    'marks_obtained' => $marksObtained,
                    'grade' => $grade,
                    'remarks' => $studentData['remarks'] ?? null,
                    'status' => 'Pending',
                    'entered_by' => auth()->id(),
                    'approved_by' => null,
                    'approved_at' => null,
                ]
            );
        }

        return redirect()
            ->route('marks.index')
            ->with(
                'success',
                'Marks recorded successfully.'
            );
    }

    public function approve(Mark $mark)
    {
        if ($mark->status === 'Approved') {
            return back()->withErrors([
                'mark' => 'This mark is already approved.'
            ]);
        }

        $mark->update([
            'status' => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('marks.index')
            ->with(
                'success',
                'Mark approved successfully.'
            );
    }

    private function calculateGrade(
        float $marks,
        float $maxMarks
    ): string {

        $percentage = ($marks / $maxMarks) * 100;

        if ($percentage >= 80) {
            return 'A';
        }

        if ($percentage >= 70) {
            return 'B';
        }

        if ($percentage >= 60) {
            return 'C';
        }

        if ($percentage >= 50) {
            return 'D';
        }

        return 'F';
    }
}