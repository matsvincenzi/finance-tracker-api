<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyReportRequest;
use App\Models\User;
use App\Services\MonthlyReport;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final class MonthlyReportController extends Controller
{
    /**
     * Income, expenses and per-category totals of one month (default: the current one), in BRL.
     */
    public function __invoke(MonthlyReportRequest $request, #[CurrentUser] User $user, MonthlyReport $report): JsonResponse
    {
        // "!" resets the fields missing from the format; otherwise the day would be today's and
        // "2026-02" requested on the 30th would overflow into March.
        $month = $request->date('month', '!Y-m') ?? today();

        return response()->json(['data' => $report->for($user, $month)]);
    }
}
