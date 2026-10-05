@extends('layouts.app')

@section('title', 'Issue Book')

@section('content')
    <div class="mb-6">
        <a href="{{ route('book-borrowings.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
            <x-icon name="clipboard-document-check" size="sm" /> Back to borrowings
        </a>
        <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
            <x-icon name="plus" class="text-primary-600" /> Issue Book
        </h1>
        <p class="mt-1 text-sm text-slate-600">Record a book loan to a student or teacher.</p>
    </div>

    <x-card>
        @if($availableCopies->isEmpty())
            <div class="flex items-start gap-3 rounded-lg bg-warning-50 p-4 text-warning-700">
                <x-icon name="books" />
                <div>
                    <p class="font-semibold">No copies available</p>
                    <p class="text-sm">No available book copies can be issued right now.</p>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('book-borrowings.store') }}" class="space-y-6">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <x-form.select name="book_copy_id" label="Book copy" required class="sm:col-span-2 lg:col-span-3">
                        <option value="">Select a copy</option>
                        @foreach($availableCopies as $copy)
                            <option value="{{ $copy->id }}" @selected((string) old('book_copy_id') === (string) $copy->id)>
                                {{ $copy->book->title }} — {{ $copy->copy_number }}
                            </option>
                        @endforeach
                    </x-form.select>
                    <x-form.select id="borrower_type" name="borrower_type" label="Borrower type" required>
                        <option value="student" @selected(old('borrower_type', 'student') === 'student')>Student</option>
                        <option value="teacher" @selected(old('borrower_type') === 'teacher')>Teacher</option>
                    </x-form.select>
                    <div id="student_borrower">
                        <x-form.select id="student_borrower_id" name="borrower_id" label="Student" required>
                            <option value="">Select a student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('borrower_type', 'student') === 'student' && (string) old('borrower_id') === (string) $student->id)>
                                    {{ $student->admission_number }} — {{ $student->first_name }} {{ $student->last_name }}
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>
                    <div id="teacher_borrower" hidden>
                        <x-form.select id="teacher_borrower_id" name="borrower_id" label="Teacher" disabled required>
                            <option value="">Select a teacher</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('borrower_type') === 'teacher' && (string) old('borrower_id') === (string) $teacher->id)>
                                    {{ $teacher->employee_number }} — {{ $teacher->first_name }} {{ $teacher->last_name }}
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>
                    <x-form.input name="borrowed_date" label="Borrowed date" type="date" :value="old('borrowed_date', today()->toDateString())" required />
                    <x-form.input name="due_date" label="Due date" type="date" :value="old('due_date', today()->addDays(config('library.loan_period_days', 14))->toDateString())" required />
                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-form.textarea name="remarks" label="Remarks" maxlength="1000" :value="old('remarks')" />
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                    <x-button type="submit" icon="clipboard-document-check">Issue Book</x-button>
                    <a href="{{ route('book-borrowings.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
                </div>
            </form>

            <script>
                const borrowerType = document.getElementById('borrower_type');
                const studentBorrower = document.getElementById('student_borrower');
                const teacherBorrower = document.getElementById('teacher_borrower');
                const studentId = document.getElementById('student_borrower_id');
                const teacherId = document.getElementById('teacher_borrower_id');

                function updateBorrowerOptions() {
                    const isStudent = borrowerType.value === 'student';
                    studentBorrower.hidden = !isStudent;
                    teacherBorrower.hidden = isStudent;
                    studentId.disabled = !isStudent;
                    teacherId.disabled = isStudent;
                    studentId.required = isStudent;
                    teacherId.required = !isStudent;
                }

                borrowerType.addEventListener('change', updateBorrowerOptions);
                updateBorrowerOptions();
            </script>
        @endif
    </x-card>
@endsection
