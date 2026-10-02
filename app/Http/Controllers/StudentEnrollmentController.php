<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentController extends Controller
{
    public function index()
    {
        $enrollments = StudentEnrollment::with([
            'student',
            'academicYear',
            'classRoom',
            'stream',
        ])
            ->orderByDesc('id')
            ->get();

        return view('enrollments.index', compact('enrollments'));
    }

    public function create()
    {
        $students = Student::orderBy('first_name')->get();

        $academicYears = AcademicYear::orderByDesc('id')->get();

        $classes = ClassRoom::orderBy('name')->get();

        $streams = Stream::with('classRoom')
            ->orderBy('name')
            ->get();

        return view('enrollments.create', compact(
            'students',
            'academicYears',
            'classes',
            'streams'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'stream_id' => ['required', 'exists:streams,id'],
            'status' => ['required', 'string', 'max:30'],
            'enrollment_date' => ['required', 'date'],
            'exit_date' => ['nullable', 'date', 'after_or_equal:enrollment_date'],
        ]);

        $alreadyEnrolled = StudentEnrollment::where('student_id', $validated['student_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->exists();

        if ($alreadyEnrolled) {
            return back()
                ->withErrors([
                    'student_id' => 'This student is already enrolled for this academic year.',
                ])
                ->withInput();
        }

        StudentEnrollment::create($validated);

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Student enrolled successfully.');
    }

    public function edit(StudentEnrollment $enrollment)
    {
        $students = Student::orderBy('first_name')->get();

        $academicYears = AcademicYear::orderByDesc('id')->get();

        $classes = ClassRoom::orderBy('name')->get();

        $streams = Stream::with('classRoom')
            ->orderBy('name')
            ->get();

        return view('enrollments.edit', compact(
            'enrollment',
            'students',
            'academicYears',
            'classes',
            'streams'
        ));
    }

    public function update(Request $request, StudentEnrollment $enrollment)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'stream_id' => ['required', 'exists:streams,id'],
            'status' => ['required', 'string', 'max:30'],
            'enrollment_date' => ['required', 'date'],
            'exit_date' => ['nullable', 'date', 'after_or_equal:enrollment_date'],
        ]);

        $alreadyEnrolled = StudentEnrollment::where('student_id', $validated['student_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('id', '!=', $enrollment->id)
            ->exists();

        if ($alreadyEnrolled) {
            return back()
                ->withErrors([
                    'student_id' => 'This student is already enrolled for this academic year.',
                ])
                ->withInput();
        }

        $enrollment->update($validated);

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Student enrollment updated successfully.');
    }

    public function destroy(StudentEnrollment $enrollment)
    {
        DB::transaction(function () use ($enrollment): void {
            $enrollment = StudentEnrollment::query()
                ->lockForUpdate()
                ->findOrFail($enrollment->id);

            $hasAttendance = DB::table('attendance')
                ->where('student_id', $enrollment->student_id)
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->where('class_id', $enrollment->class_id)
                ->exists();
            $hasMarks = DB::table('marks')
                ->join('exam_subjects', 'marks.exam_subject_id', '=', 'exam_subjects.id')
                ->join('exams', 'exam_subjects.exam_id', '=', 'exams.id')
                ->where('marks.student_id', $enrollment->student_id)
                ->where('exam_subjects.class_id', $enrollment->class_id)
                ->where('exams.academic_year_id', $enrollment->academic_year_id)
                ->exists();
            $hasPublishedResults = DB::table('result_publications')
                ->join('exams', 'result_publications.exam_id', '=', 'exams.id')
                ->where('result_publications.class_id', $enrollment->class_id)
                ->where('exams.academic_year_id', $enrollment->academic_year_id)
                ->exists();

            if ($hasAttendance || $hasMarks || $hasPublishedResults) {
                throw ValidationException::withMessages([
                    'delete' => 'This enrollment cannot be deleted because attendance, marks, or result publication records exist. Keep the enrollment for historical reporting.',
                ]);
            }

            $enrollment->delete();
        });

        return redirect()
            ->route('enrollments.index')
            ->with('success', 'Student enrollment deleted successfully.');
    }
}
