@extends(($printMode ?? false) ? 'layouts.print' : 'layouts.app')

@section('title', 'Receipt '.$payment->receipt_number)

@section('content')
<style>
        .receipt-page { color: #1e293b; }
        .receipt { background: #fff; border: 1px solid #cbd5e1; border-radius: 1rem; margin: 0 auto; max-width: 850px; padding: 2rem; }
        .school-header { border-bottom: 2px solid #2563eb; padding-bottom: 1rem; text-align: center; }
        .school-header h1 { color: #0f172a; font-size: 1.4rem; margin: .5rem 0; }
        .school-header p { color: #475569; margin: .25rem 0; }
        .school-logo { max-height: 70px; max-width: 140px; }
        .receipt-heading { align-items: center; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; padding: 1rem 0; }
        .receipt-heading h2 { color: #1d4ed8; font-size: 1.2rem; margin: 0; }
        .receipt-number { font-size: 1.35rem; font-weight: 700; margin: .5rem 0 0; }
        .receipt-date { line-height: 1.6; text-align: right; }
        .detail-section { margin-top: 1rem; }
        .detail-section h3, .reversal-section h3 { border-bottom: 1px solid #cbd5e1; color: #334155; font-size: .95rem; padding-bottom: .4rem; }
        .receipt-page table { border-collapse: collapse; margin: .5rem 0 .9rem; width: 100%; }
        .receipt-page th, .receipt-page td { border: 1px solid #cbd5e1; padding: .55rem; text-align: left; }
        .receipt-page th { background: #f1f5f9; }
        .payment-amount { color: #047857; font-size: 1.2rem; font-weight: 700; }
        .reversed-banner { background: #b91c1c; color: white; font-size: 1rem; font-weight: 700; margin: -2rem -2rem 1.5rem; padding: .8rem; text-align: center; }
        .reversal-section { border: 1px solid #fecaca; border-radius: .5rem; margin-top: 1rem; padding: .5rem .8rem; }
        .receipt-footer { align-items: end; display: flex; justify-content: space-between; margin-top: 1.8rem; }
        .receipt-footer p { color: #1d4ed8; font-size: 1rem; font-weight: 700; }
        .stamp-area { border: 1px dashed #64748b; height: 75px; padding: .5rem; text-align: center; width: 210px; }
        .receipt-toolbar { margin: 0 auto 1rem; max-width: 850px; }
        @media print {
            .receipt { border: 0; border-radius: 0; max-width: none; padding: .5rem; }
            .reversed-banner { margin: 0 0 1rem; }
            .receipt-toolbar { display: none !important; }
            a { color: inherit; text-decoration: none; }
        }
        @media (max-width: 600px) {
            .receipt { padding: 1rem; }
            .receipt-heading { align-items: flex-start; flex-direction: column; gap: .75rem; }
            .receipt-date { text-align: left; }
            .reversed-banner { margin: -1rem -1rem 1rem; }
            .receipt-page table { font-size: .75rem; }
            .receipt-page th, .receipt-page td { padding: .3rem; }
            .receipt-footer { align-items: flex-start; flex-direction: column; gap: .75rem; }
        }
</style>
<div class="receipt-page space-y-4">
    @if(! $printMode)
        <nav class="receipt-toolbar flex flex-wrap gap-2" aria-label="Receipt actions">
            <a href="{{ route('payments.show', $payment) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to payment</a>
            @if(auth()->user()->hasPermission('receipts.print'))
                <a href="{{ route('receipts.print', $payment) }}" class="inline-flex items-center gap-2 rounded-lg border border-primary-200 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50"><x-icon name="printer" size="sm" /> Print view</a>
                <a href="{{ route('receipts.pdf', $payment) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="arrow-down-tray" size="sm" /> Download PDF</a>
            @endif
        </nav>
    @else
        <nav class="receipt-toolbar flex flex-wrap gap-2" aria-label="Print actions">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white"><x-icon name="printer" size="sm" /> Print receipt</button>
            <a href="{{ route('receipts.show', $payment) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Back to receipt</a>
            <a href="{{ route('payments.show', $payment) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Back to payment</a>
        </nav>
    @endif
    @include('receipts._content')
</div>
@endsection
