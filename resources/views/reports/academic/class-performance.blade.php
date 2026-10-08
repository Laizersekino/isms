@extends('layouts.app')

@section('title', 'Class Performance Report')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Class Performance Report</h1>
            <p class="text-sm text-slate-600">{{ $classRoom->name }} — {{ $academicYear->name }} — {{ $term->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-button variant="secondary" :href="route('reports.academic.class-performance.print', ['class' => $classRoom, 'academic_year_id' => $academicYear->id, 'term_id' => $term->id])" icon="printer" target="_blank">
                Print
            </x-button>
            <x-button variant="primary" :href="route('reports.academic.class-performance.pdf', ['class' => $classRoom, 'academic_year_id' => $academicYear->id, 'term_id' => $term->id])" icon="arrow-down-tray">
                Download PDF
            </x-button>
        </div>
    </div>

    <x-card>
        <form method="GET" action="{{ route('reports.academic.class-performance', ['class' => $classRoom]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-form.select name="academic_year_id" label="Academic Year" required>
                @foreach($academicYears as $year)
                    <option value="{{ $year->id }}" @selected((int) $year->id === (int) $academicYear->id)>{{ $year->name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="term_id" label="Term" required>
                @foreach($terms as $availableTerm)
                    <option value="{{ $availableTerm->id }}" @selected((int) $availableTerm->id === (int) $term->id)>{{ $availableTerm->name }}</option>
                @endforeach
            </x-form.select>

            <div class="flex items-end">
                <x-button type="submit" variant="primary" icon="eye">
                    View Report
                </x-button>
            </div>
        </form>
    </x-card>

    <div class="mt-6">
        @include('reports.academic.partials.class-performance-content')
    </div>
@endsection