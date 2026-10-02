<?php

namespace App\Exports;

use Closure;
use Generator;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinanceReportExport implements FromGenerator, WithHeadings
{
    /**
     * @param  array<int, string>  $headings
     * @param  Closure(): iterable<array<int, mixed>>  $rows
     */
    public function __construct(
        private readonly array $headings,
        private readonly Closure $rows
    ) {}

    public function generator(): Generator
    {
        foreach (($this->rows)() as $row) {
            yield $row;
        }
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
