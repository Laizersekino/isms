<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassRoomController extends Controller
{
    public function index()
    {
        $classes = ClassRoom::orderBy('name')->get();

        return view('classes.index', compact('classes'));
    }

    public function create()
    {
        return view('classes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:classes,name'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:30'],
        ]);

        ClassRoom::create($validated);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Class created successfully.');
    }

    public function edit(ClassRoom $class)
    {
        return view('classes.edit', compact('class'));
    }

    public function update(Request $request, ClassRoom $class)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:classes,name,'.$class->id,
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:30'],
        ]);

        $class->update($validated);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Class updated successfully.');
    }

    public function destroy(ClassRoom $class)
    {
        DB::transaction(function () use ($class): void {
            $class = ClassRoom::query()->lockForUpdate()->findOrFail($class->id);

            if (
                $class->streams()->exists()
                || $class->enrollments()->exists()
                || Attendance::query()->where('class_id', $class->id)->exists()
                || $class->examSubjects()->exists()
                || TeacherAssignment::query()->where('class_id', $class->id)->exists()
                || DB::table('class_subjects')->where('class_id', $class->id)->exists()
                || DB::table('result_publications')->where('class_id', $class->id)->exists()
            ) {
                throw ValidationException::withMessages([
                    'delete' => 'This class cannot be deleted because streams, enrollments, attendance, exam subjects, or other historical records exist.',
                ]);
            }

            $class->delete();
        });

        return redirect()
            ->route('classes.index')
            ->with('success', 'Class deleted successfully.');
    }
}
