<?php

namespace App\Http\Controllers;

use App\Models\DisciplinaryCase;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class DisciplinaryCaseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:discipline.view', only: ['index', 'show']),
            new Middleware('permission:discipline.create', only: ['create', 'store']),
            new Middleware('permission:discipline.update', only: ['edit', 'update']),
            new Middleware('permission:discipline.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $query = DisciplinaryCase::with(['student', 'reportedBy', 'createdBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('offence_type')) {
            $query->where('offence_type', 'like', '%' . $request->offence_type . '%');
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $cases = $query->orderByDesc('incident_date')->paginate(20);

        return view('discipline.index', compact('cases'));
    }

    public function create(): View
    {
        $students = Student::orderBy('first_name')->get();

        return view('discipline.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'offence_type' => ['required', 'string', 'max:255'],
            'incident_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
            'reported_by' => ['nullable', 'exists:users,id'],
            'action_taken' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:open,resolved,closed'],
            'resolution_date' => ['nullable', 'date', 'after_or_equal:incident_date'],
            'follow_up' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['created_by'] = auth()->id();

        DisciplinaryCase::create($validated);

        return redirect()
            ->route('discipline.index')
            ->with('success', 'Disciplinary case created successfully.');
    }

    public function show(DisciplinaryCase $disciplinaryCase): View
    {
        $disciplinaryCase->load(['student', 'reportedBy', 'createdBy']);

        return view('discipline.show', compact('disciplinaryCase'));
    }

    public function edit(DisciplinaryCase $disciplinaryCase): View
    {
        $students = Student::orderBy('first_name')->get();

        return view('discipline.edit', compact('disciplinaryCase', 'students'));
    }

    public function update(
        Request $request,
        DisciplinaryCase $disciplinaryCase
    ): RedirectResponse {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'offence_type' => ['required', 'string', 'max:255'],
            'incident_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
            'reported_by' => ['nullable', 'exists:users,id'],
            'action_taken' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:open,resolved,closed'],
            'resolution_date' => ['nullable', 'date', 'after_or_equal:incident_date'],
            'follow_up' => ['nullable', 'string', 'max:5000'],
        ]);

        $disciplinaryCase->update($validated);

        return redirect()
            ->route('discipline.show', $disciplinaryCase)
            ->with('success', 'Disciplinary case updated successfully.');
    }

    public function destroy(DisciplinaryCase $disciplinaryCase): RedirectResponse
    {
        $disciplinaryCase->delete();

        return redirect()
            ->route('discipline.index')
            ->with('success', 'Disciplinary case deleted successfully.');
    }
}
