<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { color: #222; font-family: Arial, sans-serif; margin: 24px; }
        .receipt { border: 1px solid #bbb; margin: 0 auto; max-width: 850px; padding: 28px; }
        .school-header { border-bottom: 2px solid #333; padding-bottom: 14px; text-align: center; }
        .school-header h1 { font-size: 22px; margin: 8px 0; }
        .school-header p { margin: 4px 0; }
        .school-logo { max-height: 70px; max-width: 140px; }
        .receipt-heading { align-items: center; display: flex; justify-content: space-between; padding: 18px 0; }
        .receipt-heading h2 { font-size: 20px; margin: 0; }
        .receipt-number { font-size: 22px; font-weight: bold; margin: 8px 0 0; }
        .receipt-date { line-height: 1.6; text-align: right; }
        .detail-section { margin-top: 14px; }
        .detail-section h3, .reversal-section h3 { border-bottom: 1px solid #ccc; font-size: 15px; padding-bottom: 6px; }
        table { border-collapse: collapse; margin: 8px 0 14px; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .payment-amount { font-size: 19px; font-weight: bold; }
        .reversed-banner { background: #a40000; color: white; font-size: 20px; font-weight: bold; margin: -28px -28px 22px; padding: 13px; text-align: center; }
        .reversal-section { border: 1px solid #a40000; margin-top: 16px; padding: 8px 14px; }
        .receipt-footer { align-items: end; display: flex; justify-content: space-between; margin-top: 30px; }
        .receipt-footer p { font-size: 18px; font-weight: bold; }
        .stamp-area { border: 1px dashed #777; height: 75px; padding: 8px; text-align: center; width: 210px; }
        .toolbar { margin: 0 auto 16px; max-width: 850px; }
        .toolbar a, .toolbar button { margin-right: 8px; padding: 8px 12px; }
        @media print {
            body { margin: 0; }
            .receipt { border: 0; max-width: none; padding: 8px; }
            .reversed-banner { margin: 0 0 18px; }
            .toolbar { display: none; }
            a { color: inherit; text-decoration: none; }
        }
        @media (max-width: 600px) {
            body { margin: 8px; }
            .receipt { padding: 14px; }
            .receipt-heading { align-items: flex-start; flex-direction: column; gap: 12px; }
            .receipt-date { text-align: left; }
            .reversed-banner { margin: -14px -14px 16px; }
            table { font-size: 12px; }
            th, td { padding: 5px; }
        }
    </style>
</head>
<body>
    @if(! $printMode)
        <nav class="toolbar" aria-label="Receipt actions">
            <a href="{{ route('payments.show', $payment) }}">Back to payment</a>
            @if(auth()->user()->hasPermission('receipts.print'))
                <a href="{{ route('receipts.print', $payment) }}">Print view</a>
                <a href="{{ route('receipts.pdf', $payment) }}">Download PDF</a>
            @endif
        </nav>
    @else
        <nav class="toolbar" aria-label="Print actions">
            <button type="button" onclick="window.print()">Print receipt</button>
            <a href="{{ route('receipts.show', $payment) }}">Back to receipt</a>
            <a href="{{ route('payments.show', $payment) }}">Back to payment</a>
        </nav>
    @endif

    @include('receipts._content')
</body>
</html>
