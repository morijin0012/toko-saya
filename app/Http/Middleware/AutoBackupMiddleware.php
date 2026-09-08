<?php

namespace App\Http\Middleware;

use App\Services\AutoBackupService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AutoBackupMiddleware
{
    public function __construct(
        private readonly AutoBackupService $autoBackupService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->autoBackupService->run();
        } catch (Throwable $e) {
            report($e);
        }

        return $next($request);
    }
}
