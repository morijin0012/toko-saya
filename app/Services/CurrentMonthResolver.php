<?php

namespace App\Services;

use Carbon\Carbon;

class CurrentMonthResolver
{
    public function current(): string
    {
        return Carbon::now()->format('Y-m');
    }

    public function previous(?string $month = null): string
    {
        $date = $month
            ? Carbon::createFromFormat('Y-m', $month)
            : Carbon::now();

        return $date->copy()->subMonth()->format('Y-m');
    }
}
