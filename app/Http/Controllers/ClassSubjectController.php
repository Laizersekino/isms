<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Subject;
use Illuminate\Http\Request;

class ClassSubjectController extends Controller
{
    public function index()
    {
        $classes = ClassRoom::with('subjects')
            ->orderBy('name')
            ->get();

        return view('class_subjects.index', compact('classes'));
    }

    public function create()
    {
        $classes = ClassRoom::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();

        return view('class_subjects.create', compact('classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ]);

        $class = ClassRoom::findOrFail($validated['class_id']);

        if ($class->subjects()->where('subject_id', $validated['subject_id'])->exists()) {
            return back()
                ->withErrors(['subject_id' => 'This subject is already assigned to this class.'])
                ->withInput();
        }

        $class->subjects()->attach($validated['subject_id']);

        return redirect()
            ->route('class-subjects.index')
            ->with('success', 'Subject assigned to class successfully.');
    }

    public function destroy(ClassRoom $class, Subject $subject)
    {
        $class->subjects()->detach($subject->id);

        return redirect()
            ->route('class-subjects.index')
            ->with('success', 'Subject removed from class successfully.');
    }
}