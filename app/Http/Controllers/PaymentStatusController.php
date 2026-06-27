<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentStatusController extends Controller
{
    public function __construct()
    {
        abort_unless(hasModule('payments'), 404);
    }

    public function success(Request $request): View
    {
        abort_unless($request->hasValidSignature(), 403);

        $order = Order::withoutGlobalScopes()
            ->find($request->query('order_id'));

        return view('pages.payment-status', [
            'order' => $order,
            'status' => 'success',
            'message' => 'Your payment was received. We will notify you when your order progresses.',
        ]);
    }

    public function failure(Request $request): View
    {
        abort_unless($request->hasValidSignature(), 403);

        $order = Order::withoutGlobalScopes()
            ->find($request->query('order_id'));

        return view('pages.payment-status', [
            'order' => $order,
            'status' => 'failure',
            'message' => 'There was a problem with your payment. Please try again or contact us.',
        ]);
    }
}
