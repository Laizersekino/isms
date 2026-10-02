<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Grade;
use App\Models\Mark;
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

class ClassPerformanceReportController extends Controller
{
    public function classPerformance(ClassRoom $class, Request $request): View
    {
        $data = $this->reportData($class, $request);

        return view('reports.academic.class-performance', $data);
    }

    public function classPerformancePdf(ClassRoom $class, Request $request): Response
    {
        $data = $this->reportData($class, $request);

        return Pdf::loadView('reports.academic.pdf.class-performance', $data)
            ->download('class-performance-'.$class->id.'-'.$data['term']->id.'.pdf');
    }

    public function classPerformancePrint(ClassRoom $class, Request $request): View
    {
        return view('reports.academic.print.class-performance', $this->reportData($class, $request));
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(ClassRoom $class, Request $request): array
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_if($user->hasRole('Parent') || $user->hasRole('Student'), 403);

        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->where('academic_year_id', $request->input('academic_year_id')),
            ],
        ]);

        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
        $term = Term::findOrFail($validated['term_id']);

        if ($user->hasRole('Teacher')) {
            abort_unless(
                $user->teacher_id
                    && TeacherAssignment::query()
                        ->where('teacher_id', $user->teacher_id)
                        ->where('class_id', $class->id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->exists(),
                403,
                'You are not assigned to this class for the selected academic year.'
            );
        }

        $enrollments = StudentEnrollment::query()
            ->with('student')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active')
            ->get();
        $students = $enrollments
            ->pluck('student')
            ->filter()
            ->keyBy('id');
        $marks = $students->isEmpty()
            ? collect()
            : Mark::query()
                ->with(['student', 'examSubject.subject'])
                ->whereIn('student_id', $students->keys())
                ->where('status', 'Approved')
                ->whereHas('examSubject', function (Builder $query) use ($class, $academicYear, $term) {
                    $query->where('class_id', $class->id)
                        ->whereHas('exam', function (Builder $examQuery) use ($academicYear, $term) {
                            $examQuery->where('academic_year_id', $academicYear->id)
                                ->where('term_id', $term->id);
                        });
                })
                ->get();

        $grades = Grade::query()
            ->where('status', 'active')
            ->orderByDesc('min_percentage')
            ->get();
        if ($grades->isEmpty()) {
            $grades = collect(config('reports.grades', []));
        }

        $studentTotals = [];
        $subjectTotals = [];

        foreach ($marks as $mark) {
            $studentId = (int) $mark->student_id;
            $subject = $mark->examSubject->subject;
            if (! $subject) {
                continue;
            }

            $maxMarks = (float) $mark->examSubject->max_marks;
            $marksObtained = (float) $mark->marks_obtained;
            $studentTotals[$studentId] ??= ['marks' => 0.0, 'max' => 0.0];
            $studentTotals[$studentId]['marks'] += $marksObtained;
            $studentTotals[$studentId]['max'] += $maxMarks;

            $subjectId = (int) $subject->id;
            $subjectTotals[$subjectId] ??= [
                'name' => $subject->name,
                'students' => [],
            ];
            $subjectTotals[$subjectId]['students'][$studentId] ??= ['marks' => 0.0, 'max' => 0.0];
            $subjectTotals[$subjectId]['students'][$studentId]['marks'] += $marksObtained;
            $subjectTotals[$subjectId]['students'][$studentId]['max'] += $maxMarks;
        }

        $studentRows = [];
        foreach ($studentTotals as $studentId => $total) {
            if ($total['max'] <= 0 || ! $students->has($studentId)) {
                continue;
            }

            $average = round(($total['marks'] / $total['max']) * 100, 2);
            $student = $students->get($studentId);
            $studentRows[] = [
                'id' => $studentId,
                'name' => implode(' ', array_filter([
                    $student->first_name,
                    $student->middle_name,
                    $student->last_name,
                ])),
                'admission_number' => $student->admission_number,
                'total_marks' => round($total['marks'], 2),
                'maximum_marks' => round($total['max'], 2),
                'average' => $average,
                'grade' => $this->gradeForPercentage($average, $grades),
            ];
        }

        usort($studentRows, function (array $left, array $right): int {
            return ($right['total_marks'] <=> $left['total_marks'])
                ?: ($right['average'] <=> $left['average'])
                ?: ($left['name'] <=> $right['name']);
        });
        $previousTotal = null;
        $rank = 0;
        foreach ($studentRows as $index => &$studentRow) {
            if ($previousTotal === null || $studentRow['total_marks'] !== $previousTotal) {
                $rank = $index + 1;
                $previousTotal = $studentRow['total_marks'];
            }
            $studentRow['rank'] = $rank;
        }
        unset($studentRow);

        $passingThreshold = (float) config('reports.passing_threshold', 50);
        $gradedCount = count($studentRows);
        $passedCount = collect($studentRows)
            ->where('average', '>=', $passingThreshold)
            ->count();
        $classAverage = $gradedCount > 0
            ? round(collect($studentRows)->avg('average'), 2)
            : 0.0;

        $subjects = collect($subjectTotals)
            ->map(function (array $subject) use ($passingThreshold): array {
                $scores = collect($subject['students'])
                    ->filter(fn (array $score): bool => $score['max'] > 0)
                    ->map(fn (array $score): float => round(($score['marks'] / $score['max']) * 100, 2))
                    ->values();

                return [
                    'name' => $subject['name'],
                    'average' => $scores->isNotEmpty() ? round($scores->avg(), 2) : 0.0,
                    'highest' => $scores->isNotEmpty() ? $scores->max() : 0.0,
                    'lowest' => $scores->isNotEmpty() ? $scores->min() : 0.0,
                    'pass_rate' => $scores->isNotEmpty()
                        ? round(($scores->filter(fn (float $score): bool => $score >= $passingThreshold)->count() / $scores->count()) * 100, 2)
                        : 0.0,
                    'student_count' => $scores->count(),
                ];
            })
            ->sortBy('average')
            ->values();

        $gradeDistribution = $this->gradeDistribution($studentRows, $grades, $gradedCount);
        $studentRowsByAverage = collect($studentRows)
            ->sort(function (array $left, array $right): int {
                return ($right['average'] <=> $left['average'])
                    ?: ($right['total_marks'] <=> $left['total_marks'])
                    ?: ($left['name'] <=> $right['name']);
            })
            ->values();
        $fifthAverage = $studentRowsByAverage->get(4)['average'] ?? null;
        $topPerformers = $fifthAverage === null
            ? $studentRowsByAverage
            : $studentRowsByAverage->takeWhile(fn (array $student): bool => $student['average'] >= $fifthAverage)->values();

        return [
            'classRoom' => $class,
            'academicYear' => $academicYear,
            'term' => $term,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'terms' => Term::query()
                ->where('academic_year_id', $academicYear->id)
                ->orderBy('start_date')
                ->get(),
            'students' => $studentRows,
            'totalStudents' => $enrollments->count(),
            'studentsWithMarks' => $gradedCount,
            'classAverage' => $classAverage,
            'passingThreshold' => $passingThreshold,
            'passRate' => $gradedCount > 0 ? round(($passedCount / $gradedCount) * 100, 2) : 0.0,
            'subjects' => $subjects,
            'weakAreas' => $subjects->take(3)->values(),
            'studentsNeedingSupport' => collect($studentRows)
                ->filter(fn (array $student): bool => $student['average'] < $passingThreshold)
                ->sortBy('average')
                ->values(),
            'topPerformers' => $topPerformers,
            'gradeDistribution' => $gradeDistribution,
            'school' => config('reports.school', []),
        ];
    }

    private function gradeForPercentage(float $percentage, Collection $grades): string
    {
        foreach ($grades as $grade) {
            $minimum = (float) (is_array($grade) ? $grade['min_percentage'] : $grade->min_percentage);
            $maximum = (float) (is_array($grade) ? $grade['max_percentage'] : $grade->max_percentage);
            if ($percentage >= $minimum && $percentage <= $maximum) {
                return is_array($grade) ? (string) $grade['code'] : $grade->code;
            }
        }

        return 'N/A';
    }

    /**
     * @param  list<array<string, mixed>>  $students
     * @return Collection<int, array{code: string, count: int, percentage: float}>
     */
    private function gradeDistribution(array $students, Collection $grades, int $studentCount): Collection
    {
        $distribution = $grades
            ->map(function ($grade) use ($students, $studentCount): array {
                $code = is_array($grade) ? (string) $grade['code'] : $grade->code;
                $count = collect($students)->where('grade', $code)->count();

                return [
                    'code' => $code,
                    'count' => $count,
                    'percentage' => $studentCount > 0 ? round(($count / $studentCount) * 100, 2) : 0.0,
                ];
            })
            ->values();
        $ungradedCount = collect($students)->where('grade', 'N/A')->count();

        if ($ungradedCount > 0 || $distribution->isEmpty()) {
            $distribution->push([
                'code' => 'N/A',
                'count' => $ungradedCount,
                'percentage' => $studentCount > 0 ? round(($ungradedCount / $studentCount) * 100, 2) : 0.0,
            ]);
        }

        return $distribution;
    }
}
