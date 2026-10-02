<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\StudentEnrollment;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderByDesc('id')->get();

        return view('academic_years.index', compact('academicYears'));
    }

    public function create()
    {
        return view('academic_years.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:academic_years,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'string', 'max:30'],
        ]);

        AcademicYear::create($validated);

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Academic year created successfully.');
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('academic_years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:academic_years,name,'.$academicYear->id,
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'string', 'max:30'],
        ]);

        $academicYear->update($validated);

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Academic year updated successfully.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        DB::transaction(function () use ($academicYear): void {
            $academicYear = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->id);

            if (
                $academicYear->terms()->exists()
                || StudentEnrollment::query()->where('academic_year_id', $academicYear->id)->exists()
                || Attendance::query()->where('academic_year_id', $academicYear->id)->exists()
                || DB::table('exams')->where('academic_year_id', $academicYear->id)->exists()
                || TeacherAssignment::query()->where('academic_year_id', $academicYear->id)->exists()
                || DB::table('academic_calendar')->where('academic_year_id', $academicYear->id)->exists()
            ) {
                throw ValidationException::withMessages([
                    'delete' => 'This academic year cannot be deleted because terms, enrollments, attendance, examinations, or other dependent records exist.',
                ]);
            }

            $academicYear->delete();
        });

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Academic year deleted successfully.');
    }
}
