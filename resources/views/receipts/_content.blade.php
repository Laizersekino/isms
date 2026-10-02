<article class="receipt">
    @if($payment->isReversed())
        <div class="reversed-banner" role="alert">
            REVERSED — This payment is not a valid receipt.
        </div>
    @endif

    <header class="school-header">
        @if($schoolLogoPath)
            <img class="school-logo" src="{{ $schoolLogoPath }}" alt="{{ $school['name'] ?? config('app.name') }}">
        @endif
        <h1>{{ $school['name'] ?? config('app.name', 'Integrated School Management System') }}</h1>
        @if(! empty($school['address']))
            <p>{{ $school['address'] }}</p>
        @endif
        <p>
            @if(! empty($school['phone']))
                Phone: {{ $school['phone'] }}
            @endif
            @if(! empty($school['phone']) && ! empty($school['email']))
                &nbsp;|&nbsp;
            @endif
            @if(! empty($school['email']))
                Email: {{ $school['email'] }}
            @endif
        </p>
    </header>

    <section class="receipt-heading">
        <div>
            <h2>PAYMENT RECEIPT</h2>
            <p class="receipt-number">{{ $payment->receipt_number }}</p>
        </div>
        <div class="receipt-date">
            <strong>Date received</strong>
            <div>{{ $payment->paid_date->format('Y-m-d') }}</div>
            <strong>Status</strong>
            <div>{{ strtoupper($payment->status) }}</div>
        </div>
    </section>

    <section class="detail-section">
        <h3>Student</h3>
        <table>
            <tbody>
                <tr>
                    <th>Name</th>
                    <td>{{ $payment->student->first_name }} {{ $payment->student->middle_name }} {{ $payment->student->last_name }}</td>
                </tr>
                <tr>
                    <th>Admission number</th>
                    <td>{{ $payment->student->admission_number }}</td>
                </tr>
                <tr>
                    <th>Class</th>
                    <td>{{ $studentFee->feeStructure->classRoom->name }}</td>
                </tr>
                <tr>
                    <th>Academic period</th>
                    <td>{{ $studentFee->feeStructure->academicYear->name }} — {{ $studentFee->feeStructure->term->name }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="detail-section">
        <h3>Fee and payment</h3>
        <table>
            <thead>
                <tr>
                    <th>Fee item</th>
                    <th>Fee amount</th>
                    <th>Paid to date</th>
                    <th>Remaining balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $studentFee->feeStructureItem->name }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->amount, 2) }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->paid_amount, 2) }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $studentFee->balance, 2) }}</td>
                </tr>
            </tbody>
        </table>
        <table>
            <tbody>
                <tr>
                    <th>Payment received</th>
                    <td class="payment-amount">{{ config('library.currency', 'TZS') }} {{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Payment method</th>
                    <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                </tr>
                <tr>
                    <th>Reference number</th>
                    <td>{{ $payment->reference_number ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Recorded by</th>
                    <td>{{ $payment->recordedBy->name }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    @if($payment->isReversed())
        <section class="reversal-section">
            <h3>Reversal details</h3>
            <p><strong>Reversed on:</strong> {{ $payment->reversed_at?->format('Y-m-d H:i:s') ?? '—' }}</p>
            <p><strong>Reversed by:</strong> {{ $payment->reversedBy?->name ?? '—' }}</p>
            <p><strong>Reason:</strong> {{ $payment->reversal_reason }}</p>
        </section>
    @endif

    <footer class="receipt-footer">
        <p>Thank you.</p>
        <div class="stamp-area">School stamp / authorized signature</div>
    </footer>
</article>
