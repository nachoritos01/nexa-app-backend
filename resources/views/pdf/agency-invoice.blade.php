<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; line-height: 1.5; color: #333; padding: 40px; }
        .header { border-bottom: 3px solid #1e40af; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #1e40af; }
        .logo-img { max-height: 60px; max-width: 200px; }
        .slogan { font-size: 14px; color: #666; }
        .doc-number { float: right; text-align: right; }
        .doc-number h2 { font-size: 18px; color: #1e40af; }
        .doc-number p { font-size: 11px; color: #666; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; color: #1e40af; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f8fafc; font-weight: bold; color: #1e40af; }
        td.num, th.num { text-align: right; }
        .totals { background-color: #f0f4ff; padding: 20px; border-radius: 8px; }
        .totals table { margin: 0; }
        .totals td { border: none; padding: 8px 10px; }
        .totals .label { font-weight: bold; }
        .totals .amount { text-align: right; font-size: 14px; }
        .totals .total-row { font-size: 18px; color: #1e40af; border-top: 2px solid #1e40af; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    @php
        $currency = config('business.currency');
        $items = is_array($invoice->items) ? $invoice->items : [];
    @endphp

    <div class="header">
        <div class="doc-number">
            <h2>Factura {{ $invoice->number }}</h2>
            <p>Fecha: {{ $invoice->date }}</p>
            <p>Vencimiento: {{ $invoice->due_date }}</p>
            <p>Generada: {{ $generatedAt }}</p>
        </div>
        @if(!empty($business['logo_path']) && file_exists(storage_path('app/public/' . $business['logo_path'])))
            <img src="{{ storage_path('app/public/' . $business['logo_path']) }}" alt="{{ $business['name'] }}" class="logo-img">
        @else
            <div class="logo">{{ $business['name'] }}</div>
        @endif
        <div class="slogan">{{ $business['slogan'] }}</div>
    </div>

    @if($client)
    <div class="section">
        <div class="section-title">Cliente</div>
        <table>
            <tr><td><strong>Nombre:</strong></td><td>{{ $client->name }}</td></tr>
            @if($client->contact_name)<tr><td><strong>Contacto:</strong></td><td>{{ $client->contact_name }}</td></tr>@endif
            @if($client->email)<tr><td><strong>Email:</strong></td><td>{{ $client->email }}</td></tr>@endif
            @if($client->phone)<tr><td><strong>Teléfono:</strong></td><td>{{ $client->phone }}</td></tr>@endif
            @if($client->address)<tr><td><strong>Dirección:</strong></td><td>{{ $client->address }}</td></tr>@endif
            @if($client->tax_id)<tr><td><strong>RFC/Tax ID:</strong></td><td>{{ $client->tax_id }}</td></tr>@endif
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Conceptos</div>
        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="num">Cantidad</th>
                    <th class="num">Precio unitario</th>
                    <th class="num">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['description'] ?? 'Concepto' }}</td>
                    <td class="num">{{ $item['quantity'] ?? 1 }}</td>
                    <td class="num">${{ number_format($item['unitPrice'] ?? 0, 2) }} {{ $currency }}</td>
                    <td class="num">${{ number_format($item['total'] ?? (($item['quantity'] ?? 1) * ($item['unitPrice'] ?? 0)), 2) }} {{ $currency }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="totals">
            <table>
                <tr><td class="label">Subtotal:</td><td class="amount">${{ number_format($invoice->subtotal, 2) }} {{ $currency }}</td></tr>
                @if($invoice->tax > 0)
                <tr><td class="label">Impuesto ({{ $invoice->tax }}%):</td><td class="amount">${{ number_format($invoice->subtotal * ($invoice->tax / 100), 2) }} {{ $currency }}</td></tr>
                @endif
                <tr class="total-row"><td class="label">TOTAL:</td><td class="amount">${{ number_format($invoice->total, 2) }} {{ $currency }}</td></tr>
            </table>
        </div>
    </div>

    @if($invoice->notes)
    <div class="section">
        <div class="section-title">Notas</div>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p><strong>{{ $business['name'] }}</strong></p>
        <p>{{ $business['phone'] }} · {{ $business['email'] }}</p>
        @if(!empty($business['address']))<p>{{ $business['address'] }}</p>@endif
    </div>
</body>
</html>
