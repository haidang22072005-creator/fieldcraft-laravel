<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, AdminReportService $reports): View
    {
        $filters = $this->filters($request);

        return view('admin.reports.index', [
            'report' => $reports->build($filters),
            'filters' => $filters,
        ]);
    }

    public function charts(Request $request, AdminReportService $reports): JsonResponse
    {
        return response()->json(['data' => $reports->build($this->filters($request))]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
    }
}
