@extends('layouts.app')

@section('title', 'Record Payment')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Record Payment</h1>
        <p class="mt-1 text-sm text-slate-600">Record a payment against an outstanding student fee.</p>
    </div>
    @if($studentFees->isEmpty())
        <x-card>
            <x-empty-state icon="currency-dollar" title="No outstanding fees" message="No active student fees have an outstanding balance." />
            <a href="{{ route('payments.index') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary-700 hover:underline"><x-icon name="arrow-right" size="sm" /> Back to payments</a>
        </x-card>
    @else
        <x-card>
            <form method="POST" action="{{ route('payments.store') }}" class="space-y-6">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-1.5 md:col-span-2">
                        <label for="student_fee_id" class="block text-sm font-medium text-slate-700">Student fee</label>
                        <select id="student_fee_id" name="student_fee_id" required aria-invalid="{{ $errors->has('student_fee_id') ? 'true' : 'false' }}" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
                            <option value="">Select student fee</option>
                            @foreach($studentFees as $studentFee)
                                <option value="{{ $studentFee->id }}" data-balance="{{ number_format((float) $studentFee->balance, 2, '.', '') }}" @selected((string) old('student_fee_id', $selectedStudentFeeId) === (string) $studentFee->id)>
                                    {{ $studentFee->student->admission_number }} — {{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }} — {{ $studentFee->feeStructureItem->name }} — Balance: {{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <x-form.error name="student_fee_id" />
                    </div>
                    <x-form.input name="amount" label="Amount" type="number" step="0.01" min="0.01" :value="old('amount')" required />
                    <x-form.select name="payment_method" label="Payment method" required>
                        <option value="">Select method</option>
                        @foreach(['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'] as $method)
                            <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ str($method)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.input name="reference_number" label="Reference number (optional)" maxlength="100" :value="old('reference_number')" />
                    <x-form.input name="paid_date" label="Payment date" type="date" max="{{ today()->toDateString() }}" :value="old('paid_date', today()->toDateString())" required />
                    <div class="md:col-span-2">
                        <x-form.textarea name="remarks" label="Remarks (optional)" maxlength="1000" :value="old('remarks')" rows="3" />
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5">
                    <x-button type="submit" icon="check">Record payment</x-button>
                    <a href="{{ route('payments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
                </div>
            </form>
        </x-card>
    @endif
</div>
@endsection
