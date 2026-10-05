@extends('layouts.pdf')

@section('title', 'Receipt '.$payment->receipt_number)

@push('styles')
    <style>
        @page { margin: 28px; }
        body { color: #222; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .receipt { border: 1px solid #888; padding: 18px; }
        .school-header { border-bottom: 2px solid #333; padding-bottom: 10px; text-align: center; }
        .school-header h1 { font-size: 18px; margin: 6px 0; }
        .school-header p { margin: 3px 0; }
        .school-logo { max-height: 55px; max-width: 110px; }
        .receipt-heading { border-bottom: 1px solid #999; margin-bottom: 12px; padding: 12px 0; }
        .receipt-heading h2 { font-size: 16px; margin: 0 0 6px; }
        .receipt-number { font-size: 18px; font-weight: bold; }
        .receipt-date { line-height: 1.5; }
        .detail-section { margin-top: 10px; }
        .detail-section h3, .reversal-section h3 { border-bottom: 1px solid #bbb; font-size: 12px; padding-bottom: 4px; }
        .receipt table { border-collapse: collapse; margin: 6px 0 10px; width: 100%; }
        .receipt th, .receipt td { border: 1px solid #aaa; padding: 6px; text-align: left; }
        .receipt th { background: #eee; }
        .payment-amount { font-size: 15px; font-weight: bold; }
        .reversed-banner { background: #a40000; color: white; font-size: 16px; font-weight: bold; margin-bottom: 14px; padding: 9px; text-align: center; }
        .reversal-section { border: 1px solid #a40000; margin-top: 12px; padding: 6px 10px; }
        .receipt-footer { margin-top: 24px; }
        .receipt-footer p { font-size: 14px; font-weight: bold; }
        .stamp-area { border: 1px dashed #777; height: 62px; padding: 6px; text-align: center; width: 190px; }
    </style>
@endpush

@section('content')
    @include('receipts._content')
@endsection
