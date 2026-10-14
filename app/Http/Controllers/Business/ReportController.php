<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): View
    {
        $from = $request->filled('from')
            ? \Illuminate\Support\Carbon::parse($request->string('from')->toString())->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? \Illuminate\Support\Carbon::parse($request->string('to')->toString())->endOfDay()
            : now()->endOfDay();

        $summary = $reports->financialSummary(
            $request->user()->business_id,
            $from,
            $to,
        );

        return view('business.reports.index', [
            'summary' => $summary,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }
}
