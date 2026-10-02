<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AcademicReportController extends Controller
{
    public function show(Student $student, Request $request): View
    {
        return view('academic_reports.show', $this->reportData($student, $request, false));
    }

    public function print(Student $student, Request $request): View
    {
        return view('academic_reports.print', $this->reportData($student, $request, true));
    }

    public function pdf(Student $student, Request $request): Response
    {
        $data = $this->reportData($student, $request, true);

        if (! class_exists(Pdf::class)) {
            return response(
                'PDF report-card download is unavailable. Install it with: composer require barryvdh/laravel-dompdf',
                503,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        return Pdf::loadView('academic_reports.pdf', $data)
            ->download($student->admission_number.'-report-card.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(Student $student, Request $request, bool $standalone): array
    {
        $this->authorizeStudent($student);

        $yearId = $request->filled('academic_year_id')
            ? $request->input('academic_year_id')
            : $this->currentAcademicYear()?->id;
        $termId = $request->filled('term_id')
            ? $request->input('term_id')
            : $this->currentTerm($yearId)?->id;

        $validated = validator([
            'academic_year_id' => $yearId,
            'term_id' => $termId,
        ], [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where('academic_year_id', $yearId),
            ],
        ])->validate();

        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
        $term = Term::findOrFail($validated['term_id']);
        $enrollment = StudentEnrollment::query()
            ->with(['classRoom', 'stream'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->first();

        abort_unless($enrollment, 404, 'The student has no enrollment for this academic year.');
        $this->authorizeTeacherEnrollment($enrollment);
        $portalUser = auth()->user()->hasRole('Student') || auth()->user()->hasRole('Parent');

        $marks = Mark::query()
            ->with(['examSubject.subject', 'examSubject.exam'])
            ->where('student_id', $student->id)
            ->where('status', 'Approved')
            ->whereHas('examSubject', function (Builder $query) use ($enrollment, $academicYear, $term, $portalUser) {
                $query->where('class_id', $enrollment->class_id)
                    ->whereHas('exam', function (Builder $examQuery) use ($academicYear, $term, $enrollment, $portalUser) {
                        $examQuery->where('academic_year_id', $academicYear->id)
                            ->where('term_id', $term->id)
                            ->when($portalUser, function (Builder $publishedExamQuery) use ($enrollment) {
                                $publishedExamQuery->whereHas('resultPublications', function (Builder $publicationQuery) use ($enrollment) {
                                    $publicationQuery->where('class_id', $enrollment->class_id)
                                        ->where('status', 'Published');
                                });
                            });
                    });
            })
            ->get()
            ->sortBy(fn (Mark $mark): string => $mark->examSubject?->subject?->name ?? '')
            ->values();

        $totalMarks = round($marks->sum(fn (Mark $mark): float => (float) $mark->marks_obtained), 2);
        $maximumMarks = round($marks->sum(fn (Mark $mark): float => (float) $mark->examSubject->max_marks), 2);
        $percentage = $maximumMarks > 0
            ? round(($totalMarks / $maximumMarks) * 100, 2)
            : 0.0;

        $marks->each(function (Mark $mark): void {
            $mark->report_grade = $mark->grade ?: $this->gradeForPercentage(
                (float) $mark->marks_obtained / max((float) $mark->examSubject->max_marks, 1) * 100
            );
        });

        $classStudentIds = StudentEnrollment::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('class_id', $enrollment->class_id)
            ->pluck('student_id')
            ->unique()
            ->values();
        $rankTotals = Mark::query()
            ->select('student_id')
            ->selectRaw('SUM(marks.marks_obtained) as total_marks')
            ->whereIn('student_id', $classStudentIds)
            ->where('status', 'Approved')
            ->whereHas('examSubject', function (Builder $query) use ($enrollment, $academicYear, $term, $portalUser) {
                $query->where('class_id', $enrollment->class_id)
                    ->whereHas('exam', function (Builder $examQuery) use ($academicYear, $term, $enrollment, $portalUser) {
                        $examQuery->where('academic_year_id', $academicYear->id)
                            ->where('term_id', $term->id)
                            ->when($portalUser, function (Builder $publishedExamQuery) use ($enrollment) {
                                $publishedExamQuery->whereHas('resultPublications', function (Builder $publicationQuery) use ($enrollment) {
                                    $publicationQuery->where('class_id', $enrollment->class_id)
                                        ->where('status', 'Published');
                                });
                            });
                    });
            })
            ->groupBy('student_id')
            ->orderByDesc('total_marks')
            ->get();
        $rank = $this->rankForStudent($rankTotals, $student->id);

        $attendanceStatuses = Attendance::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('term_id', $term->id)
            ->where('class_id', $enrollment->class_id)
            ->get(['status'])
            ->countBy(fn (Attendance $record): string => strtolower($record->status));
        $attendanceTotal = $attendanceStatuses->sum();
        $attendanceAttended = $attendanceStatuses->get('present', 0) + $attendanceStatuses->get('late', 0);

        foreach ($marks as $mark) {
            $mark->report_percentage = round(
                (float) $mark->marks_obtained / max((float) $mark->examSubject->max_marks, 1) * 100,
                2
            );
        }

        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();
        $terms = Term::query()
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('start_date')
            ->get();

        return [
            'student' => $student,
            'enrollment' => $enrollment,
            'academicYear' => $academicYear,
            'term' => $term,
            'academicYears' => $academicYears,
            'terms' => $terms,
            'marks' => $marks,
            'totalMarks' => $totalMarks,
            'maximumMarks' => $maximumMarks,
            'percentage' => $percentage,
            'overallGrade' => $maximumMarks > 0 ? $this->gradeForPercentage($percentage) : 'N/A',
            'rank' => $rank,
            'classSize' => $classStudentIds->count(),
            'attendance' => [
                'total' => $attendanceTotal,
                'present' => $attendanceStatuses->get('present', 0),
                'late' => $attendanceStatuses->get('late', 0),
                'absent' => $attendanceStatuses->get('absent', 0),
                'excused' => $attendanceStatuses->get('excused', 0),
                'percentage' => $attendanceTotal > 0
                    ? round(($attendanceAttended / $attendanceTotal) * 100, 2)
                    : 0.0,
            ],
            'school' => config('reports.school', []),
            'standalone' => $standalone,
        ];
    }

    private function authorizeStudent(Student $student): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        if ($user->hasRole('Student')) {
            $studentIds = Student::query()
                ->where('email', $user->email)
                ->limit(2)
                ->pluck('id');

            abort_unless($user->email && $studentIds->count() === 1, 403);
            abort_unless($studentIds->first() === $student->id, 404);

            return;
        }

        if ($user->hasRole('Parent')) {
            $parentIds = ParentModel::query()
                ->where('email', $user->email)
                ->limit(2)
                ->pluck('id');

            abort_unless($user->email && $parentIds->count() === 1, 403);
            abort_unless(
                $student->parents()->whereKey($parentIds->first())->exists(),
                404
            );

            return;
        }
    }

    private function authorizeTeacherEnrollment(StudentEnrollment $enrollment): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->hasRole('Teacher')) {
            return;
        }

        abort_unless($user->teacher_id, 403, 'This user is not linked to a teacher account.');

        abort_unless(
            TeacherAssignment::query()
                ->where('teacher_id', $user->teacher_id)
                ->where('class_id', $enrollment->class_id)
                ->where('stream_id', $enrollment->stream_id)
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->where('status', 'active')
                ->exists(),
            404
        );
    }

    private function gradeForPercentage(float $percentage): string
    {
        $grade = Grade::query()
            ->where('status', 'active')
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->orderByDesc('min_percentage')
            ->first();

        return $grade?->code ?? 'N/A';
    }

    /**
     * @param  Collection<int, object>  $totals
     */
    private function rankForStudent(Collection $totals, int $studentId): ?int
    {
        $position = 0;
        $rank = 0;
        $previousTotal = null;

        foreach ($totals as $total) {
            $position++;
            $currentTotal = (float) $total->total_marks;

            if ($previousTotal === null || $currentTotal !== $previousTotal) {
                $rank = $position;
                $previousTotal = $currentTotal;
            }

            if ((int) $total->student_id === $studentId) {
                return $rank;
            }
        }

        return null;
    }

    private function currentAcademicYear(): ?AcademicYear
    {
        $year = AcademicYear::query()->where('is_current', true)->first();

        if ($year) {
            return $year;
        }

        $today = now()->toDateString();

        return AcademicYear::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first()
            ?? AcademicYear::query()
                ->where('status', 'active')
                ->orderByDesc('start_date')
                ->first();
    }

    private function currentTerm(int|string|null $yearId): ?Term
    {
        if (! $yearId) {
            return null;
        }

        $query = Term::query()
            ->where('academic_year_id', $yearId)
            ->where('status', 'active');
        $today = now()->toDateString();
        $term = (clone $query)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->first();

        return $term ?? $query->orderBy('start_date')->first();
    }
}
