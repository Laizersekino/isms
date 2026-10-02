<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TermController extends Controller
{
    public function index()
    {
        $terms = Term::with('academicYear')->orderByDesc('id')->get();

        return view('terms.index', compact('terms'));
    }

    public function create()
    {
        $academicYears = AcademicYear::orderByDesc('id')->get();

        return view('terms.create', compact('academicYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'string', 'max:30'],
        ]);

        Term::create($validated);

        return redirect()
            ->route('terms.index')
            ->with('success', 'Term created successfully.');
    }

    public function edit(Term $term)
    {
        $academicYears = AcademicYear::orderByDesc('id')->get();

        return view('terms.edit', compact('term', 'academicYears'));
    }

    public function update(Request $request, Term $term)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'string', 'max:30'],
        ]);

        $term->update($validated);

        return redirect()
            ->route('terms.index')
            ->with('success', 'Term updated successfully.');
    }

    public function destroy(Term $term)
    {
        DB::transaction(function () use ($term): void {
            $term = Term::query()->lockForUpdate()->findOrFail($term->id);

            if (
                Attendance::query()->where('term_id', $term->id)->exists()
                || DB::table('exams')->where('term_id', $term->id)->exists()
                || DB::table('academic_calendar')->where('term_id', $term->id)->exists()
            ) {
                throw ValidationException::withMessages([
                    'delete' => 'This term cannot be deleted because attendance, examinations, or other historical records exist.',
                ]);
            }

            $term->delete();
        });

        return redirect()
            ->route('terms.index')
            ->with('success', 'Term deleted successfully.');
    }
}
