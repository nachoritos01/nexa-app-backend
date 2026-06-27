<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\PdfGenerator;
use Illuminate\Http\Request;

class OrderPdfController extends Controller
{
    public function download(Request $request, int $order): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($request->hasValidSignature(), 403);

        $record = Order::withoutGlobalScopes()->findOrFail($order);

        abort_if($record->status === OrderStatus::Cancelled, 404);

        app()->instance('currentTenant', $record->tenant);

        $pdf = app(PdfGenerator::class)->generateOrderInline($record);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "order_{$record->id}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }
}
