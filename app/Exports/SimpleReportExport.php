<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class SimpleReportExport implements FromArray
{
    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return $this->rows;
    }
}
