@extends('layouts.app')

@section('title', 'Generate Student Fees')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Generate Student Fees</h1>
        <p class="mt-1 text-sm text-slate-600">Fees are generated for active enrollments in the selected academic year and class.</p>
    </div>
    <x-card>
        <form method="POST" action="{{ route('student-fees.generate') }}" class="space-y-6">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <x-form.select name="academic_year_id" label="Academic year" required>
                    <option value="">Select academic year</option>
                    @foreach($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id') === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select name="term_id" label="Term" required>
                    <option value="">Select term</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected((string) old('term_id') === (string) $term->id)>{{ $term->academicYear->name }} — {{ $term->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select name="class_id" label="Class" required>
                    <option value="">Select class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.input name="due_date" label="Due date" type="date" :value="old('due_date')" :min="today()->toDateString()" required />
            </div>
            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5">
                <x-button type="submit" icon="check">Generate Fees</x-button>
                <a href="{{ route('student-fees.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </x-card>
</div>
@endsection
