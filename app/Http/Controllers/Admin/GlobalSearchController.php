<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $service): JsonResponse { return response()->json(['query' => (string) $request->input('q'), 'data' => $service->search($request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q'])]); }
}
