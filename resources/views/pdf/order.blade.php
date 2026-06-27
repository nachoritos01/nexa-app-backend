<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 40px;
        }
        .header {
            border-bottom: 3px solid #1e40af;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }
        .slogan {
            font-size: 14px;
            color: #666;
        }
        .logo-img {
            max-height: 60px;
            max-width: 200px;
        }
        .order-number {
            float: right;
            text-align: right;
        }
        .order-number h2 {
            font-size: 18px;
            color: #1e40af;
        }
        .order-number .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            margin-top: 4px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-grid .row {
            display: table-row;
        }
        .info-grid .label {
            display: table-cell;
            font-weight: bold;
            padding: 4px 15px 4px 0;
            width: 140px;
            vertical-align: top;
        }
        .info-grid .value {
            display: table-cell;
            padding: 4px 0;
            vertical-align: top;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #1e40af;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            background-color: #f0f4ff;
            padding: 20px;
            border-radius: 8px;
        }
        .totals table {
            margin: 0;
        }
        .totals td {
            border: none;
            padding: 6px 10px;
        }
        .totals .label {
            font-weight: bold;
        }
        .totals .amount {
            text-align: right;
            font-size: 14px;
        }
        .totals .total-row {
            font-size: 18px;
            color: #1e40af;
            border-top: 2px solid #1e40af;
        }
        .totals .balance-row {
            font-size: 16px;
        }
        .balance-positive {
            color: #dc2626;
        }
        .balance-zero {
            color: #16a34a;
        }
        .payments-table th {
            background-color: #f0fdf4;
            color: #166534;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        .contact {
            margin-top: 10px;
        }
        .notes-box {
            background-color: #fef3c7;
            padding: 12px;
            border-radius: 8px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="order-number">
            <h2>Order #{{ $order->id }}</h2>
            <p>{{ $generatedAt }}</p>
            @php
                $statusColors = [
                    'draft' => '#6b7280',
                    'pending' => '#f59e0b',
                    'confirmed' => '#3b82f6',
                    'in_progress' => '#8b5cf6',
                    'completed' => '#16a34a',
                    'cancelled' => '#ef4444',
                ];
                $statusValue = $order->status->value ?? '';
                $statusColor = $statusColors[$statusValue] ?? '#6b7280';
            @endphp
            <span class="status" style="background-color: {{ $statusColor }};">
                {{ $order->status->label() }}
            </span>
        </div>
        @if(!empty($business['logo_path']) && file_exists(storage_path('app/public/' . $business['logo_path'])))
            <img src="{{ storage_path('app/public/' . $business['logo_path']) }}" alt="{{ $business['name'] }}" class="logo-img">
        @else
            <div class="logo">{{ $business['name'] }}</div>
        @endif
        <div class="slogan">{{ $business['slogan'] }}</div>
    </div>

    {{-- Customer --}}
    <div class="section">
        <div class="section-title">Customer</div>
        <div class="info-grid">
            <div class="row">
                <span class="label">Name:</span>
                <span class="value">{{ $order->customer_name }}</span>
            </div>
            @if($order->customer_phone)
            <div class="row">
                <span class="label">Phone:</span>
                <span class="value">{{ $order->customer_phone }}</span>
            </div>
            @endif
            @if($order->customer_email)
            <div class="row">
                <span class="label">Email:</span>
                <span class="value">{{ $order->customer_email }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Location --}}
    @if($order->location)
    <div class="section">
        <div class="section-title">Location</div>
        <div class="info-grid">
            <div class="row">
                <span class="label">Name:</span>
                <span class="value">{{ $order->location->name }}</span>
            </div>
            @if($order->location->address)
            <div class="row">
                <span class="label">Address:</span>
                <span class="value">{{ $order->location->address }}, {{ $order->location->city }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Priority --}}
    @if($order->priority && $order->priority->value !== 'normal')
    <div class="section">
        <div class="info-grid">
            <div class="row">
                <span class="label">Priority:</span>
                <span class="value">{{ $order->priority->label() }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Items --}}
    <div class="section">
        <div class="section-title">Items</div>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->lines as $line)
                <tr>
                    <td>{{ $line->item?->name ?? $line->description ?? 'Item' }}</td>
                    <td class="text-center">{{ $line->quantity }}</td>
                    <td class="text-right">${{ number_format($line->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($line->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="section">
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="amount">${{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->tax > 0)
                <tr>
                    <td class="label">Tax:</td>
                    <td class="amount">${{ number_format($order->tax, 2) }}</td>
                </tr>
                @endif
                @if($order->discount_amount > 0)
                <tr>
                    <td class="label">Discount:</td>
                    <td class="amount" style="color: #16a34a;">-${{ number_format($order->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="label">TOTAL:</td>
                    <td class="amount">${{ number_format($order->total, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Paid:</td>
                    <td class="amount">${{ number_format($order->total_paid, 2) }}</td>
                </tr>
                @php $balance = (float) $order->total - (float) $order->total_paid; @endphp
                <tr class="balance-row">
                    <td class="label">Balance:</td>
                    <td class="amount {{ $balance > 0 ? 'balance-positive' : 'balance-zero' }}">
                        ${{ number_format($balance, 2) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Payments --}}
    @if($order->payments->isNotEmpty())
    <div class="section">
        <div class="section-title">Payments</div>
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->payments as $payment)
                <tr>
                    <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $payment->method?->label() ?? $payment->method }}</td>
                    <td class="text-right">${{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notes --}}
    @if($order->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <div class="notes-box">
            {{ $order->notes }}
        </div>
    </div>
    @endif

    <div class="footer">
        <p><strong>{{ $business['name'] }}</strong></p>
        <div class="contact">
            @if(!empty($business['phone']))<p>{{ $business['phone'] }}</p>@endif
            <p>{{ $business['email'] }}</p>
            @if(!empty($business['address']))<p>{{ $business['address'] }}</p>@endif
        </div>
    </div>
</body>
</html>
