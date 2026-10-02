<?php

namespace App\Http\Controllers;

use App\Models\Mark;
use App\Models\ParentModel;
use App\Models\ResultPublication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StudentResultController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->restrictStudentAccessQuery(Student::query())
            ->with([
                'enrollments.classRoom',
                'parents',
            ]);

        $validated = $request->validate([
            'admission_number' => ['nullable', 'string', 'max:30'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'exam_id' => ['nullable', 'exists:exams,id'],
        ]);

        $students = $query
            ->when(! empty($validated['admission_number']), function (Builder $builder) use ($validated) {
                $builder->whereRaw('LOWER(admission_number) = ?', [
                    strtolower(trim($validated['admission_number']))
                ]);
            })
            ->when(! empty($validated['student_name']), function (Builder $builder) use ($validated) {
                $search = trim($validated['student_name']);
                $term = '%' . strtolower($search) . '%';

                $builder->where(function (Builder $studentBuilder) use ($term, $search) {
                    $studentBuilder
                        ->whereRaw('LOWER(first_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                        ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", [$term])
                        ->orWhereRaw('LOWER(admission_number) LIKE ?', [$term]);
                });
            })
            ->orderBy('first_name')
            ->get();

        $selectedStudent = null;
        $publishedExams = collect();
        $selectedExam = null;
        $resultRows = collect();

        if ($students->isNotEmpty()) {
            $selectedStudent = $students->first();

            $publishedExams = $this->getPublishedExamsForStudent($selectedStudent);

            if ($publishedExams->isNotEmpty()) {
                $selectedExamId = $validated['exam_id'] ?? $publishedExams->first()->exam_id;

                $selectedExam = $publishedExams->firstWhere('exam_id', $selectedExamId)
                    ?? $publishedExams->first();

                $resultRows = $this->getPublishedResultRows(
                    $selectedStudent,
                    $selectedExam->exam_id
                );
            }
        }

        return view('student_results.index', compact(
            'students',
            'selectedStudent',
            'publishedExams',
            'selectedExam',
            'resultRows',
            'validated'
        ));
    }

    public function printResult(Student $student, Request $request)
    {
        $student = $this->restrictStudentAccessQuery(
            Student::query()->whereKey($student->id)
        )->firstOrFail();

        $validated = $request->validate([
            'exam_id' => ['required', 'exists:exams,id'],
        ]);

        $publishedExams = $this->getPublishedExamsForStudent($student);

        $selectedExam = $publishedExams->firstWhere(
            'exam_id',
            $validated['exam_id']
        );

        if (! $selectedExam) {
            abort(404, 'No published result is available for the selected exam.');
        }

        $resultRows = $this->getPublishedResultRows(
            $student,
            $selectedExam->exam_id
        );

        return view('student_results.print', compact(
            'student',
            'selectedExam',
            'resultRows'
        ));
    }

    protected function restrictStudentAccessQuery(Builder $query): Builder
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        if ($user->hasRole('Student')) {
            $studentIds = Student::query()
                ->where('email', $user->email)
                ->limit(2)
                ->pluck('id');

            abort_unless($user->email && $studentIds->count() === 1, 403);

            return $query->whereKey($studentIds->first());
        }

        if ($user->hasRole('Parent')) {
            $parentIds = ParentModel::query()
                ->where('email', $user->email)
                ->limit(2)
                ->pluck('id');

            abort_unless($user->email && $parentIds->count() === 1, 403);

            return $query->whereHas('parents', function (Builder $builder) use ($parentIds) {
                $builder->where('parents.id', $parentIds->first());
            });
        }

        if (
            $user->hasPermission('students.view') ||
            $user->hasPermission('reports.view') ||
            $user->hasPermission('marks.view')
        ) {
            return $query;
        }

        if ($user->hasPermission('student.portal')) {
            $studentIds = Student::query()
                ->where('email', $user->email)
                ->limit(2)
                ->pluck('id');

            abort_unless($user->email && $studentIds->count() === 1, 403);

            return $query->whereKey($studentIds->first());
        }

        if ($user->hasPermission('parent.portal')) {
            $parentIds = ParentModel::query()
                ->where('email', $user->email)
                ->limit(2)
                ->pluck('id');

            abort_unless($user->email && $parentIds->count() === 1, 403);

            return $query->whereHas('parents', function (Builder $builder) use ($parentIds) {
                $builder->where('parents.id', $parentIds->first());
            });
        }

        abort(403, 'You do not have permission to view student results.');
    }

    protected function getPublishedExamsForStudent(Student $student): Collection
    {
        $classIds = $student->enrollments
            ->pluck('class_id')
            ->filter()
            ->unique()
            ->values();

        if ($classIds->isEmpty()) {
            return collect();
        }

        return ResultPublication::query()
            ->with([
                'exam.academicYear',
                'exam.term',
                'classRoom',
            ])
            ->where('status', 'Published')
            ->whereIn('class_id', $classIds)
            ->orderByDesc('published_at')
            ->get()
            ->unique('exam_id')
            ->values();
    }

    protected function getPublishedResultRows(
        Student $student,
        int $examId
    ): Collection {
        $publishedClassIds = ResultPublication::query()
            ->where('exam_id', $examId)
            ->where('status', 'Published')
            ->pluck('class_id');

        if ($publishedClassIds->isEmpty()) {
            return collect();
        }

        $studentClassIds = $student->enrollments
            ->pluck('class_id')
            ->filter()
            ->unique()
            ->values();

        $matches = Mark::query()
            ->with([
                'examSubject.subject',
                'examSubject.exam',
                'examSubject.classRoom',
            ])
            ->where('student_id', $student->id)
            ->where('status', 'Approved')
            ->whereHas('examSubject', function (Builder $query) use (
                $examId,
                $publishedClassIds,
                $studentClassIds
            ) {
                $query->where('exam_id', $examId)
                    ->whereIn('class_id', $publishedClassIds)
                    ->whereIn('class_id', $studentClassIds);
            })
            ->get();

        return $matches
            ->sortBy(
                fn ($mark) => $mark->examSubject?->subject?->name ?? ''
            )
            ->values();
    }
}