<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RefundWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function processing(Request $request, Order $order, RefundWorkflowService $workflow): JsonResponse
    {
        return response()->json(['data' => $workflow->markProcessing($order, $request->user()->id)]);
    }

    public function confirm(Request $request, Order $order, RefundWorkflowService $workflow): JsonResponse
    {
        $reference = $request->validate(['refund_reference' => ['required', 'string', 'max:100']])['refund_reference'];
        return response()->json(['data' => $workflow->confirmRefund($order, $reference, $request->user()->id)]);
    }

    public function failed(Request $request, Order $order, RefundWorkflowService $workflow): JsonResponse
    {
        $reason = $request->validate(['refund_reason' => ['required', 'string', 'max:2000']])['refund_reason'];
        return response()->json(['data' => $workflow->markFailed($order, $reason, $request->user()->id)]);
    }
}
