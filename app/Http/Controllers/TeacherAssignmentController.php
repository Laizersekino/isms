<?php

namespace App\Http\Controllers;

use App\Models\TeacherAssignment;
use App\Models\Teacher;
use App\Models\ClassRoom;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    public function index()
    {
        $assignments = TeacherAssignment::with([
            'teacher',
            'classRoom',
            'stream',
            'subject',
            'academicYear'
        ])
        ->orderByDesc('id')
        ->get();

        return view('teacher_assignments.index', compact('assignments'));
    }

    public function create()
    {
        $teachers = Teacher::orderBy('first_name')->get();
        $classes = ClassRoom::orderBy('name')->get();
        $streams = Stream::with('classRoom')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('id')->get();

        return view(
            'teacher_assignments.create',
            compact(
                'teachers',
                'classes',
                'streams',
                'subjects',
                'academicYears'
            )
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'stream_id' => ['required', 'exists:streams,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $exists = TeacherAssignment::where([
            'teacher_id' => $validated['teacher_id'],
            'class_id' => $validated['class_id'],
            'stream_id' => $validated['stream_id'],
            'subject_id' => $validated['subject_id'],
            'academic_year_id' => $validated['academic_year_id'],
        ])->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'teacher_id' => 'This teacher is already assigned to this class, stream and subject for this academic year.'
                ])
                ->withInput();
        }

        TeacherAssignment::create($validated);

        return redirect()
            ->route('teacher-assignments.index')
            ->with('success', 'Teacher assignment created successfully.');
    }

    public function edit(TeacherAssignment $teacherAssignment)
    {
        $teachers = Teacher::orderBy('first_name')->get();
        $classes = ClassRoom::orderBy('name')->get();
        $streams = Stream::with('classRoom')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('id')->get();

        return view(
            'teacher_assignments.edit',
            compact(
                'teacherAssignment',
                'teachers',
                'classes',
                'streams',
                'subjects',
                'academicYears'
            )
        );
    }

    public function update(Request $request, TeacherAssignment $teacherAssignment)
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'stream_id' => ['required', 'exists:streams,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $exists = TeacherAssignment::where([
            'teacher_id' => $validated['teacher_id'],
            'class_id' => $validated['class_id'],
            'stream_id' => $validated['stream_id'],
            'subject_id' => $validated['subject_id'],
            'academic_year_id' => $validated['academic_year_id'],
        ])
        ->where('id', '!=', $teacherAssignment->id)
        ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'teacher_id' => 'This teacher is already assigned to this class, stream and subject for this academic year.'
                ])
                ->withInput();
        }

        $teacherAssignment->update($validated);

        return redirect()
            ->route('teacher-assignments.index')
            ->with('success', 'Teacher assignment updated successfully.');
    }

    public function destroy(TeacherAssignment $teacherAssignment)
    {
        $teacherAssignment->delete();

        return redirect()
            ->route('teacher-assignments.index')
            ->with('success', 'Teacher assignment deleted successfully.');
    }
}