@extends('layouts.crud')

@section('content')
<h1>Student Fees</h1>

<p>
    <a href="{{ route('dashboard') }}">Dashboard</a>
    @if(auth()->user()->hasPermission('student_fees.generate'))
        <a href="{{ route('student-fees.generate-form') }}" class="primary">Generate Student Fees</a>
    @endif
</p>

<form method="GET" action="{{ route('student-fees.index') }}">
    <label for="search">Search student or fee</label>
    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Name, admission number, fee">

    <label for="academic_year_id">Academic year</label>
    <select id="academic_year_id" name="academic_year_id">
        <option value="">All years</option>
        @foreach($academicYears as $academicYear)
            <option value="{{ $academicYear->id }}" @selected((string) ($filters['academic_year_id'] ?? '') === (string) $academicYear->id)>
                {{ $academicYear->name }}
            </option>
        @endforeach
    </select>

    <label for="term_id">Term</label>
    <select id="term_id" name="term_id">
        <option value="">All terms</option>
        @foreach($terms as $term)
            <option value="{{ $term->id }}" @selected((string) ($filters['term_id'] ?? '') === (string) $term->id)>
                {{ $term->academicYear->name }} — {{ $term->name }}
            </option>
        @endforeach
    </select>

    <label for="fee_structure_id">Fee structure</label>
    <select id="fee_structure_id" name="fee_structure_id">
        <option value="">All structures</option>
        @foreach($feeStructures as $feeStructure)
            <option value="{{ $feeStructure->id }}" @selected((string) ($filters['fee_structure_id'] ?? '') === (string) $feeStructure->id)>
                {{ $feeStructure->name }}
            </option>
        @endforeach
    </select>

    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="">All statuses</option>
        @foreach(['unpaid', 'partially_paid', 'paid', 'waived'] as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                {{ str($status)->replace('_', ' ')->title() }}
            </option>
        @endforeach
    </select>

    <button type="submit">Filter</button>
    @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '')) > 0 || $search !== '')
        <a href="{{ route('student-fees.index') }}">Clear</a>
    @endif
</form>

@if($studentFees->isEmpty())
    <p>No student fees found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Fee</th>
                <th>Academic period</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Due date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($studentFees as $studentFee)
                <tr>
                    <td>
                        <a href="{{ route('student-fees.show', $studentFee) }}">
                            {{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }}
                        </a>
                        <br>{{ $studentFee->student->admission_number }}
                    </td>
                    <td>{{ $studentFee->feeStructureItem->name }}</td>
                    <td>
                        {{ $studentFee->feeStructure->academicYear->name }}
                        — {{ $studentFee->feeStructure->term->name }}
                    </td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->amount, 2) }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->paid_amount, 2) }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->balance, 2) }}</td>
                    <td>{{ $studentFee->due_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ str($studentFee->status)->replace('_', ' ')->title() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $studentFees->links() }}
@endif
@endsection
