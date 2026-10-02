<?php

namespace App\Http\Controllers;

use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $examSubjectsQuery = ExamSubject::with([
            'exam',
            'subject',
            'classRoom',
        ]);

        $teacher = auth()->user()->hasRole('Teacher')
            ? $this->currentTeacher()
            : null;

        if ($teacher) {
            $examSubjectsQuery->whereExists(function (QueryBuilder $query) use ($teacher): void {
                $query->selectRaw('1')
                    ->from('teacher_assignments')
                    ->join('exams', 'exams.id', '=', 'exam_subjects.exam_id')
                    ->join('streams', 'streams.id', '=', 'teacher_assignments.stream_id')
                    ->whereColumn('teacher_assignments.class_id', 'exam_subjects.class_id')
                    ->whereColumn('teacher_assignments.subject_id', 'exam_subjects.subject_id')
                    ->whereColumn('teacher_assignments.academic_year_id', 'exams.academic_year_id')
                    ->whereColumn('streams.class_id', 'teacher_assignments.class_id')
                    ->where('teacher_assignments.teacher_id', $teacher->id)
                    ->where('teacher_assignments.status', 'active');
            });
        }

        $examSubjects = $examSubjectsQuery
            ->orderByDesc('id')
            ->get();

        $selectedExamSubject = null;
        $students = collect();

        if ($request->filled('exam_subject_id')) {
            $selectedExamSubject = $examSubjects->firstWhere(
                'id',
                $request->integer('exam_subject_id')
            );

            if (! $selectedExamSubject) {
                abort(404, 'Exam subject not found.');
            }

            $studentQuery = Student::query()
                ->whereHas('enrollments', function (Builder $query) use ($selectedExamSubject) {
                    $query->where('class_id', $selectedExamSubject->class_id)
                        ->where('academic_year_id', $selectedExamSubject->exam->academic_year_id)
                        ->where('status', 'active');
                });

            if ($teacher) {
                $streamIds = $this->assignmentsForExamSubject($selectedExamSubject, $teacher)
                    ->pluck('stream_id')
                    ->all();

                $studentQuery->whereHas('enrollments', function (Builder $query) use ($selectedExamSubject, $streamIds) {
                    $query->where('class_id', $selectedExamSubject->class_id)
                        ->where('academic_year_id', $selectedExamSubject->exam->academic_year_id)
                        ->where('status', 'active')
                        ->whereIn('stream_id', $streamIds);
                });
            }

            $students = $studentQuery
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
                'integer',
                'exists:exam_subjects,id',
            ],
            'students' => [
                'required',
                'array',
                'min:1',
            ],
            'students.*.marks_obtained' => [
                'required',
                'numeric',
                'min:0',
            ],
            'students.*.remarks' => [
                'nullable',
                'string',
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $examSubject = ExamSubject::with(['exam.term'])
                ->findOrFail($validated['exam_subject_id']);
            $exam = $examSubject->exam;

            if (
                ! $exam
                || ! $exam->term
                || (int) $exam->term->academic_year_id !== (int) $exam->academic_year_id
            ) {
                throw ValidationException::withMessages([
                    'exam_subject_id' => 'The selected exam must have a term in the same academic year.',
                ]);
            }

            $maxMarks = (float) $examSubject->max_marks;

            if ($maxMarks <= 0) {
                throw ValidationException::withMessages([
                    'exam_subject_id' => 'The selected exam subject has an invalid maximum mark.',
                ]);
            }

            $teacher = auth()->user()->hasRole('Teacher')
                ? $this->currentTeacher()
                : null;
            $assignments = $teacher
                ? $this->assignmentsForExamSubject($examSubject, $teacher)
                : collect();
            $preparedMarks = [];

            foreach ($validated['students'] as $studentId => $studentData) {
                if (filter_var((string) $studentId, FILTER_VALIDATE_INT) === false) {
                    throw ValidationException::withMessages([
                        'students' => 'Each student entry must use a valid student ID.',
                    ]);
                }

                $student = Student::findOrFail((int) $studentId);
                $enrollment = StudentEnrollment::query()
                    ->where('student_id', $student->id)
                    ->where('class_id', $examSubject->class_id)
                    ->where('academic_year_id', $exam->academic_year_id)
                    ->where('status', 'active')
                    ->where(function (Builder $query) use ($examSubject): void {
                        $query->whereNull('stream_id')
                            ->orWhereHas('stream', function (Builder $streamQuery) use ($examSubject): void {
                                $streamQuery->where('class_id', $examSubject->class_id);
                            });
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $enrollment) {
                    throw ValidationException::withMessages([
                        'students' => 'Each selected student must have an active enrollment in the exam subject class and academic year.',
                    ]);
                }

                if (
                    $teacher
                    && ! $assignments->contains(function (TeacherAssignment $assignment) use ($enrollment): bool {
                        return $assignment->stream_id === null
                            || (string) $assignment->stream_id === (string) $enrollment->stream_id;
                    })
                ) {
                    abort(403, 'You are not assigned to the selected student stream for this exam subject.');
                }

                $marksObtained = (float) $studentData['marks_obtained'];

                if ($marksObtained > $maxMarks) {
                    throw ValidationException::withMessages([
                        'students' => 'Marks obtained cannot exceed the maximum marks of '.$maxMarks.'.',
                    ]);
                }

                $preparedMarks[$student->id] = [
                    'marks_obtained' => $marksObtained,
                    'grade' => $this->calculateGrade($marksObtained, $maxMarks),
                    'remarks' => $studentData['remarks'] ?? null,
                ];
            }

            $existingMarks = Mark::query()
                ->where('exam_subject_id', $examSubject->id)
                ->whereIn('student_id', array_keys($preparedMarks))
                ->lockForUpdate()
                ->get()
                ->keyBy('student_id');

            if ($existingMarks->contains(fn (Mark $mark): bool => $mark->status === 'Approved')) {
                throw ValidationException::withMessages([
                    'students' => 'Approved marks cannot be changed through the normal mark-entry form.',
                ]);
            }

            foreach ($preparedMarks as $studentId => $markData) {
                $mark = $existingMarks->get($studentId) ?? new Mark([
                    'exam_subject_id' => $examSubject->id,
                    'student_id' => $studentId,
                ]);

                $mark->fill([
                    ...$markData,
                    'status' => 'Pending',
                    'entered_by' => auth()->id(),
                    'approved_by' => null,
                    'approved_at' => null,
                ])->save();
            }
        });

        return redirect()
            ->route('marks.index')
            ->with('success', 'Marks recorded successfully.');
    }

    public function approve(Mark $mark)
    {
        if ($mark->status === 'Approved') {
            return back()->withErrors([
                'mark' => 'This mark is already approved.',
            ]);
        }

        $mark->update([
            'status' => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('marks.index')
            ->with('success', 'Mark approved successfully.');
    }

    private function currentTeacher(): Teacher
    {
        $teacher = auth()->user()->teacher;

        abort_unless($teacher, 403, 'This user is not linked to a teacher account.');

        return $teacher;
    }

    private function assignmentsForExamSubject(
        ExamSubject $examSubject,
        Teacher $teacher
    ): EloquentCollection {
        return TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $examSubject->class_id)
            ->where('subject_id', $examSubject->subject_id)
            ->where('academic_year_id', $examSubject->exam->academic_year_id)
            ->where('status', 'active')
            ->whereHas('stream', function (Builder $query) use ($examSubject): void {
                $query->where('class_id', $examSubject->class_id);
            })
            ->get();
    }

    private function calculateGrade(float $marks, float $maxMarks): string
    {
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
