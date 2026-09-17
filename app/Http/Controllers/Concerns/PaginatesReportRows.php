<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginatesReportRows
{
    private const int ROWS_PER_PAGE = 10;

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function paginateRows(Request $request, array $rows): LengthAwarePaginator
    {
        $perPage = self::ROWS_PER_PAGE;
        $total = count($rows);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $request->integer('page', 1)), $lastPage);

        return (new LengthAwarePaginator(
            collect($rows)->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ],
        ))->withQueryString()->fragment('results');
    }
}
