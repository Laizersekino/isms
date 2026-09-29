<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Subject;
use App\Models\ClassRoom;
use Illuminate\Http\Request;

class TeacherSubjectController extends Controller
{
    public function index()
    {
        $teachers = Teacher::with('subjects')
            ->orderBy('first_name')
            ->get();

        return view('teacher_subjects.index', compact('teachers'));
    }

    public function create()
    {
        $teachers = Teacher::orderBy('first_name')->get();
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassRoom::orderBy('name')->get();

        return view(
            'teacher_subjects.create',
            compact('teachers', 'subjects', 'classes')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_id' => ['required', 'exists:classes,id'],
        ]);

        $teacher = Teacher::findOrFail($validated['teacher_id']);

        if (
            $teacher->subjects()
                ->where('subject_id', $validated['subject_id'])
                ->wherePivot('class_id', $validated['class_id'])
                ->exists()
        ) {
            return back()
                ->withErrors([
                    'subject_id' =>
                        'This subject is already assigned to this teacher for this class.'
                ])
                ->withInput();
        }

        $teacher->subjects()->attach(
            $validated['subject_id'],
            [
                'class_id' => $validated['class_id'],
            ]
        );

        return redirect()
            ->route('teacher-subjects.index')
            ->with('success', 'Subject assigned to teacher successfully.');
    }

    public function destroy(Teacher $teacher, Subject $subject)
    {
        $teacher->subjects()->detach($subject->id);

        return redirect()
            ->route('teacher-subjects.index')
            ->with('success', 'Subject removed from teacher successfully.');
    }
}