<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\ClassRoom;
use App\Models\Stream;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display all attendance records.
     */
    public function index()
    {
        $attendance = Attendance::with([
            'student',
            'academicYear',
            'term',
            'classRoom',
            'stream',
            'recordedBy'
        ])
        ->orderByDesc('attendance_date')
        ->get();

        return view('attendance.index', compact('attendance'));
    }


    /**
     * Show bulk attendance form.
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        // Get the teacher linked to the logged-in user
        $teacher = $user->teacher;

        if (!$teacher) {
            abort(403, 'This user is not linked to a teacher account.');
        }

        // Get only active assignments belonging to this teacher
        $assignments = $teacher->assignments()
            ->with([
                'classRoom',
                'stream',
                'subject',
                'academicYear'
            ])
            ->where('status', 'active')
            ->get();

        $selectedAssignment = null;
        $students = collect();

        // If an assignment has been selected
        if ($request->filled('assignment_id')) {

            $selectedAssignment = $assignments->firstWhere(
                'id',
                $request->assignment_id
            );

            // Prevent teacher from using another teacher's assignment
            if (!$selectedAssignment) {
                abort(
                    403,
                    'You are not authorized to use this assignment.'
                );
            }

            // Get active students enrolled in this class,
            // stream and academic year
            $students = Student::whereHas('enrollments', function ($query) use ($selectedAssignment) {

                $query->where(
                    'academic_year_id',
                    $selectedAssignment->academic_year_id
                )
                ->where(
                    'class_id',
                    $selectedAssignment->class_id
                )
                ->where(
                    'stream_id',
                    $selectedAssignment->stream_id
                )
                ->where(
                    'status',
                    'active'
                );

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
     * Store bulk attendance for all selected students.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Get the teacher linked to the logged-in user
        $teacher = $user->teacher;

        if (!$teacher) {
            abort(403, 'This user is not linked to a teacher account.');
        }

        // Validate attendance information
        $validated = $request->validate([
            'academic_year_id' => [
                'required',
                'exists:academic_years,id'
            ],

            'class_id' => [
                'required',
                'exists:classes,id'
            ],

            'stream_id' => [
                'required',
                'exists:streams,id'
            ],

            'attendance_date' => [
                'required',
                'date'
            ],

            'students' => [
                'required',
                'array'
            ],

            'students.*.status' => [
                'required',
                'in:Present,Absent,Late,Excused'
            ],

            'students.*.remarks' => [
                'nullable',
                'string'
            ],
        ]);


        // Check whether this teacher is assigned
        // to this class, stream and academic year
        $assignment = $teacher->assignments()
            ->where(
                'class_id',
                $validated['class_id']
            )
            ->where(
                'stream_id',
                $validated['stream_id']
            )
            ->where(
                'academic_year_id',
                $validated['academic_year_id']
            )
            ->where(
                'status',
                'active'
            )
            ->first();

        if (!$assignment) {
            abort(
                403,
                'You are not authorized to record attendance for this class and stream.'
            );
        }


        // Find the academic term for the selected date
        $term = Term::where(
            'academic_year_id',
            $validated['academic_year_id']
        )
        ->whereDate(
            'start_date',
            '<=',
            $validated['attendance_date']
        )
        ->whereDate(
            'end_date',
            '>=',
            $validated['attendance_date']
        )
        ->first();


        if (!$term) {
            return back()
                ->withErrors([
                    'attendance_date' =>
                        'No academic term is active for the selected date.'
                ])
                ->withInput();
        }


        // Get only students who are actually enrolled
        // in the selected class, stream and academic year
        $students = Student::whereHas('enrollments', function ($query) use ($validated) {

            $query->where(
                'academic_year_id',
                $validated['academic_year_id']
            )
            ->where(
                'class_id',
                $validated['class_id']
            )
            ->where(
                'stream_id',
                $validated['stream_id']
            )
            ->where(
                'status',
                'active'
            );

        })
        ->whereIn(
            'id',
            array_keys($validated['students'])
        )
        ->get();


        // Save attendance for each student
        foreach ($students as $student) {

            $studentData = $validated['students'][$student->id];

            Attendance::updateOrCreate(

                [
                    'student_id' => $student->id,

                    'academic_year_id' =>
                        $validated['academic_year_id'],

                    'term_id' =>
                        $term->id,

                    'class_id' =>
                        $validated['class_id'],

                    'stream_id' =>
                        $validated['stream_id'],

                    'attendance_date' =>
                        $validated['attendance_date'],
                ],

                [
                    'status' =>
                        $studentData['status'],

                    'recorded_time' =>
                        now(),

                    'recorded_by' =>
                        auth()->id(),

                    'remarks' =>
                        $studentData['remarks'] ?? null,
                ]
            );
        }


        return redirect()
            ->route('attendance.index')
            ->with(
                'success',
                'Attendance for all students was recorded successfully.'
            );
    }


    /**
     * Show one attendance record for editing.
     */
    public function edit(Attendance $attendance)
    {
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
     * Update one attendance record.
     */
    public function update(
        Request $request,
        Attendance $attendance
    ) {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'exists:students,id'
            ],

            'academic_year_id' => [
                'required',
                'exists:academic_years,id'
            ],

            'term_id' => [
                'required',
                'exists:terms,id'
            ],

            'class_id' => [
                'required',
                'exists:classes,id'
            ],

            'stream_id' => [
                'required',
                'exists:streams,id'
            ],

            'attendance_date' => [
                'required',
                'date'
            ],

            'status' => [
                'required',
                'in:Present,Absent,Late,Excused'
            ],

            'remarks' => [
                'nullable',
                'string'
            ],
        ]);


        $attendance->update($validated);


        return redirect()
            ->route('attendance.index')
            ->with(
                'success',
                'Attendance updated successfully.'
            );
    }


    /**
     * Delete one attendance record.
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return redirect()
            ->route('attendance.index')
            ->with(
                'success',
                'Attendance deleted successfully.'
            );
    }
}