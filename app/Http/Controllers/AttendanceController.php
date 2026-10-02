<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * Display attendance records available to the authenticated user.
     */
    public function index()
    {
        $query = Attendance::with([
            'student',
            'academicYear',
            'term',
            'classRoom',
            'stream',
            'recordedBy',
        ]);

        if (auth()->user()->hasRole('Teacher')) {
            $this->scopeToTeacherAssignments($query, $this->currentTeacher());
        }

        $attendance = $query
            ->orderByDesc('attendance_date')
            ->get();

        return view('attendance.index', compact('attendance'));
    }

    /**
     * Show the bulk attendance form.
     */
    public function create(Request $request)
    {
        $assignments = $this->activeAssignmentsForCurrentUser();
        $selectedAssignment = null;
        $students = collect();

        if ($request->filled('assignment_id')) {
            $selectedAssignment = $assignments->firstWhere('id', $request->assignment_id);

            if (! $selectedAssignment) {
                abort(403, 'You are not authorized to use this assignment.');
            }

            $students = Student::query()
                ->whereHas('enrollments', function ($query) use ($selectedAssignment) {
                    $query->where('academic_year_id', $selectedAssignment->academic_year_id)
                        ->where('class_id', $selectedAssignment->class_id)
                        ->where('stream_id', $selectedAssignment->stream_id)
                        ->where('status', 'active');
                })
                ->orderBy('first_name')
                ->get();
        }

        return view('attendance.create', [
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
            'students' => $students,
        ]);
    }

    /**
     * Store bulk attendance for actively enrolled students.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignment_id' => ['nullable', 'integer', 'exists:teacher_assignments,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'stream_id' => ['required', 'exists:streams,id'],
            'attendance_date' => ['required', 'date'],
            'students' => ['required', 'array', 'min:1'],
            'students.*' => ['required', 'array'],
            'students.*.status' => ['required', 'in:Present,Absent,Late,Excused'],
            'students.*.remarks' => ['nullable', 'string'],
        ]);

        $this->assertTeacherAssignmentContext(
            $validated['class_id'],
            $validated['stream_id'],
            $validated['academic_year_id'],
            $validated['assignment_id'] ?? null
        );
        $this->assertStreamBelongsToClass($validated['stream_id'], $validated['class_id']);

        $studentIds = [];
        foreach (array_keys($validated['students']) as $studentKey) {
            $studentId = filter_var($studentKey, FILTER_VALIDATE_INT);

            if (
                $studentId === false
                || $studentId < 1
                || (string) $studentId !== (string) $studentKey
            ) {
                throw ValidationException::withMessages([
                    'students' => 'The selected students are invalid.',
                ]);
            }

            $studentIds[] = $studentId;
        }

        $enrolledStudentIds = StudentEnrollment::query()
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('class_id', $validated['class_id'])
            ->where('stream_id', $validated['stream_id'])
            ->where('status', 'active')
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (count($enrolledStudentIds) !== count(array_unique($studentIds))) {
            throw ValidationException::withMessages([
                'students' => 'Attendance can only be recorded for students actively enrolled in this class, stream, and academic year.',
            ]);
        }

        $term = $this->termForDate(
            $validated['academic_year_id'],
            $validated['attendance_date']
        );

        if (! $term) {
            throw ValidationException::withMessages([
                'attendance_date' => 'No academic term is active for the selected date.',
            ]);
        }

        foreach ($studentIds as $studentId) {
            $studentData = $validated['students'][$studentId];

            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'term_id' => $term->id,
                    'class_id' => $validated['class_id'],
                    'stream_id' => $validated['stream_id'],
                    'attendance_date' => $validated['attendance_date'],
                ],
                [
                    'status' => $studentData['status'],
                    'recorded_time' => now(),
                    'recorded_by' => auth()->id(),
                    'remarks' => $studentData['remarks'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Attendance for all students was recorded successfully.');
    }

    /**
     * Show one attendance record for editing.
     */
    public function edit(Attendance $attendance)
    {
        $this->assertTeacherCanAccessRecord($attendance);

        $students = Student::orderBy('first_name')->get();
        $academicYears = AcademicYear::orderByDesc('id')->get();
        $terms = Term::orderByDesc('id')->get();
        $classes = ClassRoom::orderBy('name')->get();
        $streams = Stream::with('classRoom')
            ->orderBy('name')
            ->get();

        return view('attendance.edit', compact(
            'attendance',
            'students',
            'academicYears',
            'terms',
            'classes',
            'streams'
        ));
    }

    /**
     * Update one attendance record after validating its complete context.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $this->assertTeacherCanAccessRecord($attendance);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'stream_id' => ['required', 'exists:streams,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:Present,Absent,Late,Excused'],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->assertTeacherAssignmentContext(
            $validated['class_id'],
            $validated['stream_id'],
            $validated['academic_year_id']
        );
        $this->assertStreamBelongsToClass($validated['stream_id'], $validated['class_id']);

        $enrolled = StudentEnrollment::query()
            ->where('student_id', $validated['student_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('class_id', $validated['class_id'])
            ->where('stream_id', $validated['stream_id'])
            ->where('status', 'active')
            ->exists();

        $termMatchesContext = Term::query()
            ->whereKey($validated['term_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->whereDate('start_date', '<=', $validated['attendance_date'])
            ->whereDate('end_date', '>=', $validated['attendance_date'])
            ->exists();

        if (! $enrolled || ! $termMatchesContext) {
            throw ValidationException::withMessages([
                'student_id' => 'The student, class, stream, academic year, term, and attendance date must form a valid active enrollment context.',
            ]);
        }

        $attendance->update($validated);

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Attendance updated successfully.');
    }

    /**
     * Delete one attendance record.
     */
    public function destroy(Attendance $attendance)
    {
        $this->assertTeacherCanAccessRecord($attendance);
        $attendance->delete();

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Attendance deleted successfully.');
    }

    private function activeAssignmentsForCurrentUser()
    {
        $query = TeacherAssignment::query()
            ->with(['classRoom', 'stream', 'subject', 'academicYear'])
            ->where('status', 'active');

        if (auth()->user()->hasRole('Teacher')) {
            $query->where('teacher_id', $this->currentTeacher()->id);
        }

        return $query->get();
    }

    private function currentTeacher(): Teacher
    {
        $teacher = auth()->user()->teacher;

        abort_unless($teacher, 403, 'This user is not linked to a teacher account.');

        return $teacher;
    }

    private function scopeToTeacherAssignments(Builder $query, Teacher $teacher): void
    {
        $query->whereExists(function (QueryBuilder $assignments) use ($teacher) {
            $assignments->selectRaw('1')
                ->from('teacher_assignments')
                ->whereColumn('teacher_assignments.class_id', 'attendance.class_id')
                ->whereColumn('teacher_assignments.stream_id', 'attendance.stream_id')
                ->whereColumn('teacher_assignments.academic_year_id', 'attendance.academic_year_id')
                ->where('teacher_assignments.teacher_id', $teacher->id)
                ->where('teacher_assignments.status', 'active');
        });
    }

    private function assertTeacherCanAccessRecord(Attendance $attendance): void
    {
        if (! auth()->user()->hasRole('Teacher')) {
            return;
        }

        $this->assertTeacherAssignmentContext(
            $attendance->class_id,
            $attendance->stream_id,
            $attendance->academic_year_id
        );
    }

    private function assertTeacherAssignmentContext(
        int|string $classId,
        int|string $streamId,
        int|string $academicYearId,
        int|string|null $assignmentId = null
    ): void {
        $query = TeacherAssignment::query()
            ->where('class_id', $classId)
            ->where('stream_id', $streamId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active');

        if (auth()->user()->hasRole('Teacher')) {
            $query->where('teacher_id', $this->currentTeacher()->id);
        }

        if ($assignmentId !== null) {
            $query->whereKey($assignmentId);
        } elseif (! auth()->user()->hasRole('Teacher')) {
            return;
        }

        abort_unless(
            $query->exists(),
            403,
            'You are not authorized to use this class, stream, and academic-year assignment.'
        );
    }

    private function assertStreamBelongsToClass(int|string $streamId, int|string $classId): void
    {
        $matches = Stream::query()
            ->whereKey($streamId)
            ->where('class_id', $classId)
            ->exists();

        if (! $matches) {
            throw ValidationException::withMessages([
                'stream_id' => 'The selected stream does not belong to the selected class.',
            ]);
        }
    }

    private function termForDate(int|string $academicYearId, string $date): ?Term
    {
        return Term::query()
            ->where('academic_year_id', $academicYearId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }
}
