<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::all();
        $canManageStudentPortalAccounts = auth()->user()->hasPermission(
            'students.portal_accounts.manage'
        );
        $uniqueStudentEmails = [];
        $existingUserEmails = [];
        $studentPortalEmails = [];

        if ($canManageStudentPortalAccounts) {
            $emails = $students
                ->pluck('email')
                ->filter()
                ->map(fn (string $email): string => mb_strtolower(trim($email)))
                ->unique()
                ->values();
            $uniqueStudentEmails = $students
                ->filter(fn (Student $student): bool => (bool) $student->email)
                ->groupBy(fn (Student $student): string => mb_strtolower(trim((string) $student->email)))
                ->filter(fn ($studentsWithEmail): bool => $studentsWithEmail->count() === 1)
                ->keys()
                ->all();
            $existingUserEmails = User::query()
                ->whereIn(DB::raw('LOWER(TRIM(email))'), $emails)
                ->select(DB::raw('LOWER(TRIM(email)) as normalized_email'))
                ->pluck('normalized_email')
                ->all();
            $studentPortalEmails = User::query()
                ->whereIn(DB::raw('LOWER(TRIM(email))'), $emails)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'Student');
                })
                ->whereDoesntHave('roles', function ($query) {
                    $query->where('name', '!=', 'Student');
                })
                ->select(DB::raw('LOWER(TRIM(email)) as normalized_email'))
                ->pluck('normalized_email')
                ->all();
        }

        return view('students.index', compact(
            'students',
            'uniqueStudentEmails',
            'existingUserEmails',
            'studentPortalEmails',
            'canManageStudentPortalAccounts'
        ));
    }

    public function create()
    {
        return view('students.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'admission_number' => ['required', 'unique:students,admission_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:Male,Female'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ]);

        Student::create($validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Student created successfully.');
    }

    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'admission_number' => [
                'required',
                'unique:students,admission_number,'.$student->id,
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:Male,Female'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ]);

        $student->update($validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student): void {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);

            if (
                $student->enrollments()->exists()
                || $student->attendance()->exists()
                || $student->marks()->exists()
                || $student->parents()->exists()
                || $student->borrowings()->exists()
            ) {
                throw ValidationException::withMessages([
                    'delete' => 'This student cannot be deleted because enrollment, attendance, examination, or other historical records exist. Deactivate or archive the student instead.',
                ]);
            }

            $student->delete();
        });

        return redirect()
            ->route('students.index')
            ->with('success', 'Student deleted successfully.');
    }
}
