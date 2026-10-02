<?php

namespace App\Http\Controllers;

use App\Exports\FinanceReportExport;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class FinanceReportController extends Controller implements HasMiddleware
{
    private const PAYMENT_METHODS = [
        'cash',
        'bank_transfer',
        'mobile_money',
        'cheque',
        'other',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports.finance.view'),
        ];
    }

    public function collectionSummary(Request $request): View|Response
    {
        $format = $this->requestedFormat($request);
        if ($response = $this->unavailableExportResponse($format)) {
            return $response;
        }

        $filters = $this->academicFilters($request);
        $totals = $this->filteredFeeMetricsQuery($filters)
            ->selectRaw(
                'COALESCE(SUM(sf.amount), 0) AS total_expected, '.
                'COALESCE(SUM(COALESCE(payment_totals.collected_amount, 0)), 0) AS total_collected'
            )
            ->first();
        $totalExpected = (string) ($totals->total_expected ?? '0.00');
        $totalCollected = (string) ($totals->total_collected ?? '0.00');
        $expectedInCents = $this->amountInCents($totalExpected);
        $collectedInCents = $this->amountInCents($totalCollected);
        $classQuery = $this->classCollectionQuery($filters);
        $classes = $format === 'pdf'
            ? $classQuery->get()
            : $classQuery->paginate(25)->withQueryString();

        return $this->reportResponse(
            $request,
            $format,
            'reports.finance.collection-summary',
            [
                'title' => 'Fee Collection Summary',
                'filters' => $filters,
                'academicYears' => $this->academicYears(),
                'terms' => $this->terms(),
                'classes' => ClassRoom::query()->orderBy('name')->get(['id', 'name']),
                'classBreakdown' => $classes,
                'totalExpected' => $totalExpected,
                'totalCollected' => $totalCollected,
                'totalOutstanding' => $this->centsToAmount($expectedInCents - $collectedInCents),
                'collectionRate' => $expectedInCents > 0
                    ? round(($collectedInCents / $expectedInCents) * 100, 2)
                    : 0,
            ],
            'fee-collection-summary',
            ['Class', 'Students', 'Expected', 'Collected', 'Outstanding', 'Collection rate'],
            fn (): iterable => $this->classCollectionQuery($filters)
                ->cursor()
                ->map(fn (object $row): array => [
                    $row->class_name,
                    $row->student_count,
                    $this->csvAmount($row->total_expected),
                    $this->csvAmount($row->total_collected),
                    $this->csvAmount($row->outstanding),
                    number_format((float) $row->collection_rate, 2, '.', ''),
                ])
        );
    }

    public function outstanding(Request $request): View|Response
    {
        $format = $this->requestedFormat($request);
        if ($response = $this->unavailableExportResponse($format)) {
            return $response;
        }

        $filters = $this->academicFilters($request, true);
        $outstandingQuery = $this->outstandingStudentsQuery($filters);
        $students = $format === 'pdf'
            ? $outstandingQuery->get()
            : $outstandingQuery->paginate(25)->withQueryString();

        return $this->reportResponse(
            $request,
            $format,
            'reports.finance.outstanding',
            [
                'title' => 'Outstanding Fees',
                'filters' => $filters,
                'academicYears' => $this->academicYears(),
                'terms' => $this->terms(),
                'classes' => ClassRoom::query()->orderBy('name')->get(['id', 'name']),
                'students' => $students,
            ],
            'outstanding-fees',
            ['Student', 'Admission number', 'Class', 'Expected', 'Collected', 'Balance'],
            fn (): iterable => $this->outstandingStudentsQuery($filters)
                ->cursor()
                ->map(fn (object $row): array => [
                    trim($row->first_name.' '.$row->last_name),
                    $row->admission_number,
                    $row->class_name,
                    $this->csvAmount($row->total_expected),
                    $this->csvAmount($row->total_collected),
                    $this->csvAmount($row->balance),
                ])
        );
    }

    public function dailyCollection(Request $request): View|Response
    {
        $format = $this->requestedFormat($request);
        if ($response = $this->unavailableExportResponse($format)) {
            return $response;
        }

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);
        $date = $validated['date'] ?? today()->toDateString();
        $filters = ['date' => $date];
        $totalCollected = (string) Payment::query()
            ->completed()
            ->whereDate('paid_date', $date)
            ->sum('amount');
        $methodQuery = $this->dailyMethodBreakdownQuery($date);
        $methods = $methodQuery->get();
        $paymentQuery = Payment::query()
            ->completed()
            ->whereDate('paid_date', $date)
            ->with(['student', 'studentFee.feeStructureItem'])
            ->orderBy('paid_date')
            ->orderBy('id');
        $payments = $format === 'pdf'
            ? $paymentQuery->get()
            : $paymentQuery->paginate(25)->withQueryString();

        return $this->reportResponse(
            $request,
            $format,
            'reports.finance.daily-collection',
            [
                'title' => 'Daily Collection',
                'filters' => $filters,
                'methods' => $methods,
                'payments' => $payments,
                'totalCollected' => $totalCollected,
            ],
            'daily-collection-'.$date,
            ['Date', 'Receipt', 'Student', 'Admission number', 'Fee item', 'Method', 'Amount', 'Reference'],
            fn (): iterable => $this->dailyPaymentsQuery($date)
                ->cursor()
                ->map(fn (object $row): array => [
                    $row->paid_date,
                    $row->receipt_number,
                    trim($row->first_name.' '.$row->last_name),
                    $row->admission_number,
                    $row->fee_item,
                    $row->payment_method,
                    $this->csvAmount($row->amount),
                    $row->reference_number,
                ])
        );
    }

    public function classCollection(Request $request): View|Response
    {
        $format = $this->requestedFormat($request);
        if ($response = $this->unavailableExportResponse($format)) {
            return $response;
        }

        $filters = $this->academicFilters($request);
        $classQuery = $this->classCollectionQuery($filters);
        $classes = $format === 'pdf'
            ? $classQuery->get()
            : $classQuery->paginate(25)->withQueryString();

        return $this->reportResponse(
            $request,
            $format,
            'reports.finance.class-collection',
            [
                'title' => 'Class-wise Collection',
                'filters' => $filters,
                'academicYears' => $this->academicYears(),
                'terms' => $this->terms(),
                'classes' => $classes,
            ],
            'class-wise-collection',
            ['Class', 'Students', 'Expected', 'Collected', 'Outstanding', 'Collection rate'],
            fn (): iterable => $this->classCollectionQuery($filters)
                ->cursor()
                ->map(fn (object $row): array => [
                    $row->class_name,
                    $row->student_count,
                    $this->csvAmount($row->total_expected),
                    $this->csvAmount($row->total_collected),
                    $this->csvAmount($row->outstanding),
                    number_format((float) $row->collection_rate, 2, '.', ''),
                ])
        );
    }

    public function paymentMethods(Request $request): View|Response
    {
        $format = $this->requestedFormat($request);
        if ($response = $this->unavailableExportResponse($format)) {
            return $response;
        }

        $defaultStart = today()->startOfMonth()->toDateString();
        $defaultEnd = today()->toDateString();
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $filters = [
            'date_from' => $filters['date_from'] ?? $defaultStart,
            'date_to' => $filters['date_to'] ?? $defaultEnd,
        ];
        $methods = $this->paymentMethodBreakdownQuery($filters)->get();

        return $this->reportResponse(
            $request,
            $format,
            'reports.finance.payment-methods',
            [
                'title' => 'Payment Methods',
                'filters' => $filters,
                'methods' => $methods,
            ],
            'payment-methods',
            ['Payment method', 'Payments', 'Amount'],
            fn (): iterable => $this->paymentMethodBreakdownQuery($filters)
                ->cursor()
                ->map(fn (object $row): array => [
                    str($row->payment_method)->replace('_', ' ')->title()->toString(),
                    $row->payment_count,
                    $this->csvAmount($row->total_collected),
                ])
        );
    }

    public function studentStatement(Request $request, Student $student): View|Response
    {
        $format = $this->requestedFormat($request);
        if ($response = $this->unavailableExportResponse($format)) {
            return $response;
        }

        $studentFees = StudentFee::query()
            ->where('student_id', $student->id)
            ->with([
                'feeStructureItem',
                'feeStructure.academicYear',
                'feeStructure.term',
                'feeStructure.classRoom',
                'payments' => fn ($query) => $query->orderBy('paid_date')->orderBy('id'),
            ])
            ->orderBy('id')
            ->get();

        $totalExpectedCents = 0;
        $totalCollectedCents = 0;
        $transactions = [];
        foreach ($studentFees as $studentFee) {
            $totalExpectedCents += $this->amountInCents((string) $studentFee->amount);

            foreach ($studentFee->payments as $payment) {
                $transactions[] = [
                    'payment' => $payment,
                    'feeItem' => $studentFee->feeStructureItem->name,
                ];

                if ($payment->status === 'completed') {
                    $totalCollectedCents += $this->amountInCents((string) $payment->amount);
                }
            }
        }

        usort($transactions, function (array $first, array $second): int {
            $dateComparison = $first['payment']->paid_date <=> $second['payment']->paid_date;

            return $dateComparison !== 0
                ? $dateComparison
                : $first['payment']->id <=> $second['payment']->id;
        });

        $runningBalanceCents = $totalExpectedCents;
        foreach ($transactions as &$transaction) {
            if ($transaction['payment']->status === 'completed') {
                $runningBalanceCents -= $this->amountInCents((string) $transaction['payment']->amount);
            }

            $transaction['runningBalance'] = $this->centsToAmount($runningBalanceCents);
        }
        unset($transaction);

        $statementFees = $studentFees->map(function (StudentFee $studentFee): array {
            $collectedCents = $studentFee->payments
                ->where('status', 'completed')
                ->sum(fn (Payment $payment): int => $this->amountInCents((string) $payment->amount));
            $amountCents = $this->amountInCents((string) $studentFee->amount);

            return [
                'studentFee' => $studentFee,
                'totalCollected' => $this->centsToAmount($collectedCents),
                'balance' => $this->centsToAmount($amountCents - $collectedCents),
            ];
        });
        $totalExpected = $this->centsToAmount($totalExpectedCents);
        $totalCollected = $this->centsToAmount($totalCollectedCents);
        $csvRows = static function () use ($statementFees, $transactions): iterable {
            foreach ($statementFees as $statementFee) {
                yield [
                    'Fee',
                    '',
                    '',
                    $statementFee['studentFee']->feeStructureItem->name,
                    $statementFee['studentFee']->amount,
                    '',
                    '',
                    $statementFee['balance'],
                ];
            }

            foreach ($transactions as $transaction) {
                yield [
                    'Payment',
                    $transaction['payment']->paid_date->toDateString(),
                    $transaction['payment']->receipt_number,
                    $transaction['feeItem'],
                    '',
                    $transaction['payment']->amount,
                    $transaction['payment']->status,
                    $transaction['runningBalance'],
                ];
            }
        };

        return $this->reportResponse(
            $request,
            $format,
            'reports.finance.student-statement',
            [
                'title' => 'Student Fee Statement',
                'student' => $student,
                'studentFees' => $statementFees,
                'transactions' => $transactions,
                'totalExpected' => $totalExpected,
                'totalCollected' => $totalCollected,
                'totalOutstanding' => $this->centsToAmount($totalExpectedCents - $totalCollectedCents),
            ],
            'student-statement-'.$student->admission_number,
            ['Record type', 'Date', 'Receipt', 'Fee item', 'Charge', 'Payment', 'Status', 'Running balance'],
            $csvRows
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $headers
     * @param  Closure(): iterable<array<int, mixed>>  $csvRows
     */
    private function reportResponse(
        Request $request,
        ?string $format,
        string $view,
        array $data,
        string $filename,
        array $headers,
        Closure $csvRows
    ): View|Response {
        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $csvRows): void {
                $stream = fopen('php://output', 'w');
                if ($stream === false) {
                    throw new \RuntimeException('Unable to open the CSV output stream.');
                }

                fputcsv($stream, $headers, ',', '"', '\\');
                foreach ($csvRows() as $row) {
                    fputcsv($stream, array_map($this->protectCsvCell(...), $row), ',', '"', '\\');
                }

                fclose($stream);
            }, $filename.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        if ($format === 'excel') {
            $export = new FinanceReportExport($headers, $csvRows);

            return Excel::download($export, $filename.'.xlsx');
        }

        if ($format === 'pdf') {
            return Pdf::loadView($view, $data + ['forPdf' => true])
                ->download($filename.'.pdf');
        }

        return view($view, $data + ['forPdf' => false]);
    }

    private function requestedFormat(Request $request): ?string
    {
        $validated = $request->validate([
            'format' => ['nullable', Rule::in(['pdf', 'csv', 'excel'])],
        ]);
        $format = $validated['format'] ?? null;

        if ($format !== null) {
            abort_unless($request->user()?->hasPermission('reports.finance.export'), 403);
        }

        return $format;
    }

    private function unavailableExportResponse(?string $format): ?Response
    {
        if ($format === 'pdf' && ! class_exists(Pdf::class)) {
            return response(
                'PDF export is unavailable. Install it with: composer require barryvdh/laravel-dompdf',
                503,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        if (
            $format === 'excel'
            && (! class_exists(Excel::class) || ! interface_exists(FromGenerator::class))
        ) {
            return response(
                'Excel export is unavailable. Install it with: composer require maatwebsite/excel',
                503,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        return null;
    }

    /**
     * @param  array<string, int|string>  $filters
     */
    private function filteredFeeMetricsQuery(array $filters): QueryBuilder
    {
        $paymentTotals = DB::table('payments')
            ->select('student_fee_id')
            ->selectRaw('SUM(amount) AS collected_amount')
            ->where('status', 'completed')
            ->groupBy('student_fee_id');

        return DB::table('student_fees as sf')
            ->join('fee_structures as fs', 'fs.id', '=', 'sf.fee_structure_id')
            ->join('classes as c', 'c.id', '=', 'fs.class_id')
            ->leftJoinSub($paymentTotals, 'payment_totals', 'payment_totals.student_fee_id', '=', 'sf.id')
            ->whereNull('sf.deleted_at')
            ->whereNull('fs.deleted_at')
            ->when($filters['academic_year_id'] ?? null, fn (QueryBuilder $query, int|string $id) => $query->where('fs.academic_year_id', $id))
            ->when($filters['term_id'] ?? null, fn (QueryBuilder $query, int|string $id) => $query->where('fs.term_id', $id))
            ->when($filters['class_id'] ?? null, fn (QueryBuilder $query, int|string $id) => $query->where('fs.class_id', $id));
    }

    /**
     * @param  array<string, int|string>  $filters
     */
    private function classCollectionQuery(array $filters): QueryBuilder
    {
        $expected = 'SUM(sf.amount)';
        $collected = 'SUM(COALESCE(payment_totals.collected_amount, 0))';
        $query = $this->filteredFeeMetricsQuery($filters);

        return $query
            ->select('c.id as class_id', 'c.name as class_name')
            ->selectRaw('COUNT(DISTINCT sf.student_id) AS student_count')
            ->selectRaw($expected.' AS total_expected')
            ->selectRaw($collected.' AS total_collected')
            ->selectRaw($expected.' - '.$collected.' AS outstanding')
            ->selectRaw(
                'CASE WHEN '.$expected.' = 0 THEN 0 ELSE ROUND(('.$collected.' * 100.0 / NULLIF('.$expected.', 0)), 2) END AS collection_rate'
            )
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name');
    }

    /**
     * @param  array<string, int|string|float>  $filters
     */
    private function outstandingStudentsQuery(array $filters): QueryBuilder
    {
        $expected = 'SUM(sf.amount)';
        $collected = 'SUM(COALESCE(payment_totals.collected_amount, 0))';
        $balance = $expected.' - '.$collected;
        $query = $this->filteredFeeMetricsQuery($filters)
            ->join('students as s', 's.id', '=', 'sf.student_id')
            ->select('s.id as student_id', 's.first_name', 's.last_name', 's.admission_number')
            ->selectRaw('GROUP_CONCAT(DISTINCT c.name) AS class_name')
            ->selectRaw($expected.' AS total_expected')
            ->selectRaw($collected.' AS total_collected')
            ->selectRaw($balance.' AS balance')
            ->groupBy('s.id', 's.first_name', 's.last_name', 's.admission_number')
            ->havingRaw($balance.' > 0');

        if (isset($filters['minimum_balance'])) {
            $query->havingRaw($balance.' >= ?', [$filters['minimum_balance']]);
        }

        return $query
            ->orderByRaw('balance DESC')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name');
    }

    private function dailyMethodBreakdownQuery(string $date): QueryBuilder
    {
        return DB::table('payments')
            ->select('payment_method')
            ->selectRaw('COUNT(*) AS payment_count, SUM(amount) AS total_collected')
            ->where('status', 'completed')
            ->whereDate('paid_date', $date)
            ->groupBy('payment_method')
            ->orderBy('payment_method');
    }

    private function dailyPaymentsQuery(string $date): QueryBuilder
    {
        return DB::table('payments as p')
            ->join('students as s', 's.id', '=', 'p.student_id')
            ->join('student_fees as sf', 'sf.id', '=', 'p.student_fee_id')
            ->join('fee_structure_items as fsi', 'fsi.id', '=', 'sf.fee_structure_item_id')
            ->where('p.status', 'completed')
            ->whereDate('p.paid_date', $date)
            ->select(
                'p.id',
                'p.receipt_number',
                'p.paid_date',
                'p.amount',
                'p.payment_method',
                'p.reference_number',
                's.first_name',
                's.last_name',
                's.admission_number',
                'fsi.name as fee_item'
            )
            ->orderBy('p.paid_date')
            ->orderBy('p.id');
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function paymentMethodBreakdownQuery(array $filters): QueryBuilder
    {
        return DB::table('payments')
            ->select('payment_method')
            ->selectRaw('COUNT(*) AS payment_count, SUM(amount) AS total_collected')
            ->where('status', 'completed')
            ->whereDate('paid_date', '>=', $filters['date_from'])
            ->whereDate('paid_date', '<=', $filters['date_to'])
            ->groupBy('payment_method')
            ->orderBy('payment_method');
    }

    /**
     * @return array<string, int|string>
     */
    private function academicFilters(Request $request, bool $includeMinimumBalance = false): array
    {
        $rules = [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
        ];
        if ($includeMinimumBalance) {
            $rules['minimum_balance'] = ['nullable', 'numeric', 'min:0'];
        }

        return $request->validate($rules);
    }

    private function academicYears(): Collection
    {
        return AcademicYear::query()->orderByDesc('name')->get(['id', 'name']);
    }

    private function terms(): Collection
    {
        return Term::query()
            ->with('academicYear:id,name')
            ->orderBy('start_date')
            ->get(['id', 'academic_year_id', 'name']);
    }

    private function protectCsvCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^\s*[=+\-@]/u', $value) === 1
            ? "'".$value
            : $value;
    }

    private function csvAmount(int|float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function amountInCents(string|int|float $amount): int
    {
        $normalized = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $normalized, 2);

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function centsToAmount(int $amountInCents): string
    {
        $whole = intdiv(abs($amountInCents), 100);
        $fraction = abs($amountInCents) % 100;

        return ($amountInCents < 0 ? '-' : '').sprintf('%d.%02d', $whole, $fraction);
    }
}
