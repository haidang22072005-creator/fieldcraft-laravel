<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FootballTrend;
use App\Services\FootballApiAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrendController extends Controller
{
    public function index(): JsonResponse { return response()->json(['data' => FootballTrend::with('creator')->latest()->get(), 'provider' => app(FootballApiAdapter::class)->fetch()]); }
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'trend_data' => ['nullable', 'array'], 'campaign_suggestion' => ['nullable', 'string', 'max:2000'], 'status' => ['nullable', 'in:draft,ready']]);
        $trend = FootballTrend::create(array_merge($data, ['source' => 'manual', 'provider' => 'manual', 'created_by' => $request->user()->id]));
        return response()->json(['data' => $trend->load('creator')], 201);
    }
    public function provider(Request $request, FootballApiAdapter $provider): JsonResponse { return response()->json(['data' => $provider->fetch($request->validate(['team' => ['nullable', 'string', 'max:100'], 'date' => ['nullable', 'date']]))]); }
}
