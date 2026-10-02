<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:receipts.view', only: ['show']),
            new Middleware('permission:receipts.print', only: ['print', 'pdf']),
        ];
    }

    public function show(Payment $payment): View
    {
        return view('receipts.show', $this->receiptData($payment, false));
    }

    public function print(Payment $payment): View
    {
        return view('receipts.show', $this->receiptData($payment, true));
    }

    public function pdf(Payment $payment): Response
    {
        if (! class_exists(Pdf::class)) {
            return response(
                'PDF receipt download is unavailable. Install it with: composer require barryvdh/laravel-dompdf',
                503,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        $data = $this->receiptData($payment, true);
        $filename = $payment->receipt_number.'.pdf';

        return Pdf::loadView('receipts.pdf', $data)
            ->download($filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptData(Payment $payment, bool $printMode): array
    {
        $payment->load([
            'student',
            'studentFee.student',
            'studentFee.feeStructureItem',
            'studentFee.feeStructure.classRoom',
            'studentFee.feeStructure.academicYear',
            'studentFee.feeStructure.term',
            'recordedBy',
            'reversedBy',
        ]);

        $school = config('receipts.school', []);
        $logoPath = $school['logo_path'] ?? null;
        $schoolLogoPath = is_string($logoPath) && $logoPath !== ''
            ? public_path($logoPath)
            : null;

        return [
            'payment' => $payment,
            'studentFee' => $payment->studentFee,
            'school' => $school,
            'schoolLogoPath' => $schoolLogoPath !== null && is_file($schoolLogoPath)
                ? $schoolLogoPath
                : null,
            'printMode' => $printMode,
        ];
    }
}
