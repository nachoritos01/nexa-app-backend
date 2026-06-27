<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote #{{ $quote->id }}</title>
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
        .quote-number {
            float: right;
            text-align: right;
        }
        .quote-number h2 {
            font-size: 18px;
            color: #1e40af;
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
            padding: 8px 10px;
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
        .deposit-highlight {
            background-color: #dcfce7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            text-align: center;
        }
        .deposit-highlight .amount {
            font-size: 24px;
            font-weight: bold;
            color: #166534;
        }
        .terms {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            font-size: 11px;
        }
        .terms ul {
            margin-left: 20px;
        }
        .terms li {
            margin-bottom: 5px;
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
        .contact-btn {
            display: inline-block;
            background-color: #4f46e5;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="quote-number">
            <h2>Quote #{{ $quote->id }}</h2>
            <p>{{ $generatedAt }}</p>
        </div>
        @if(!empty($business['logo_path']) && file_exists(storage_path('app/public/' . $business['logo_path'])))
            <img src="{{ storage_path('app/public/' . $business['logo_path']) }}" alt="{{ $business['name'] }}" class="logo-img">
        @else
            <div class="logo">{{ $business['name'] }}</div>
        @endif
        <div class="slogan">{{ $business['slogan'] }}</div>
    </div>

    @if($quote->customer_name || $quote->customer_phone)
    <div class="section">
        <div class="section-title">Customer</div>
        <table>
            @if($quote->customer_name)
            <tr>
                <td><strong>Name:</strong></td>
                <td>{{ $quote->customer_name }}</td>
            </tr>
            @endif
            @if($quote->customer_phone)
            <tr>
                <td><strong>Phone:</strong></td>
                <td>{{ $quote->customer_phone }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Quote Details</div>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php $items = is_array($quote->items) ? $quote->items : []; @endphp
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['description'] ?? 'Item' }}</td>
                    <td>{{ $item['quantity'] ?? 1 }}</td>
                    <td>${{ number_format($item['unit_price'] ?? 0, 2) }} {{ config('business.currency') }}</td>
                    <td>${{ number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2) }} {{ config('business.currency') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="amount">${{ number_format($quote->subtotal, 2) }} {{ config('business.currency') }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label">TOTAL:</td>
                    <td class="amount">${{ number_format($quote->subtotal, 2) }} {{ config('business.currency') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Terms and Conditions</div>
        <div class="terms">
            <ul>
                @foreach($business['terms'] as $term)
                <li>{{ $term }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="footer">
        <p><strong>{{ $business['name'] }}</strong></p>
        <div class="contact">
            <p>📱 Phone: {{ $business['phone'] }}</p>
            <p>📧 {{ $business['email'] }}</p>
            <p>📍 {{ $business['address'] }}</p>
        </div>
        <p style="margin-top: 15px; font-style: italic;">
            Thank you for your quote!
        </p>
    </div>
</body>
</html>
