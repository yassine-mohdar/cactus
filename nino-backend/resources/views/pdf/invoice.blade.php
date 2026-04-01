<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 28px 32px 34px;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #17302A;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
            background: #FCFBF8;
        }

        .shell {
            border: 1px solid #D9E6DD;
            background: #FFFFFF;
        }

        .hero {
            background: #17302A;
            color: #F7FBF8;
            padding: 28px 30px 26px;
        }

        .hero-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-mark {
            display: inline-block;
            width: 44px;
            height: 44px;
            line-height: 44px;
            text-align: center;
            border-radius: 12px;
            background: #ECF4EE;
            color: #245848;
            font-weight: 700;
            letter-spacing: 0.24em;
            font-size: 16px;
        }

        .brand-name {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin: 10px 0 4px;
        }

        .hero-note {
            color: #DCE9E1;
            font-size: 11px;
        }

        .invoice-chip {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid rgba(236, 244, 238, 0.32);
            border-radius: 999px;
            font-size: 10px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #ECF4EE;
        }

        .invoice-number {
            margin-top: 14px;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .hero-meta {
            margin-top: 8px;
            font-size: 11px;
            color: #DCE9E1;
        }

        .section {
            padding: 24px 30px 0;
        }

        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .card {
            background: #F8FBF9;
            border: 1px solid #D9E6DD;
            padding: 14px 16px;
            vertical-align: top;
        }

        .eyebrow {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #6E7F78;
            margin-bottom: 6px;
        }

        .value {
            font-size: 15px;
            font-weight: 700;
            color: #17302A;
        }

        .value-mono {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 14px;
            font-weight: 700;
            color: #17302A;
        }

        .address-box {
            width: 48%;
            vertical-align: top;
            background: #FAF8F4;
            border: 1px solid rgba(120, 112, 95, 0.18);
            padding: 16px 18px;
        }

        .address-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #6E7F78;
            margin-bottom: 10px;
        }

        .address-strong {
            font-weight: 700;
            color: #17302A;
            margin-bottom: 6px;
        }

        .items-wrap {
            padding: 24px 30px 0;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid rgba(120, 112, 95, 0.18);
        }

        .items thead th {
            background: #FAF8F4;
            color: #61706B;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(120, 112, 95, 0.18);
            text-align: left;
        }

        .items tbody td {
            padding: 14px;
            border-bottom: 1px solid rgba(120, 112, 95, 0.12);
            vertical-align: top;
        }

        .items tbody tr:last-child td {
            border-bottom: none;
        }

        .align-right {
            text-align: right;
        }

        .item-name {
            font-weight: 700;
            color: #17302A;
            margin-bottom: 3px;
        }

        .item-sub {
            color: #61706B;
            font-size: 11px;
        }

        .totals {
            width: 320px;
            margin-left: auto;
            margin-top: 18px;
            border-collapse: collapse;
        }

        .totals td {
            padding: 7px 0;
        }

        .totals-label {
            color: #61706B;
        }

        .totals-value {
            text-align: right;
            font-family: DejaVu Sans Mono, monospace;
            color: #17302A;
        }

        .discount {
            color: #C45143;
        }

        .grand-row td {
            border-top: 1px solid rgba(120, 112, 95, 0.24);
            padding-top: 12px;
            font-size: 14px;
            font-weight: 700;
            color: #17302A;
        }

        .footer {
            padding: 22px 30px 28px;
            font-size: 10px;
            color: #6E7F78;
        }

        .footer-rule {
            border-top: 1px solid #D9E6DD;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="hero">
            <table class="hero-table">
                <tr>
                    <td style="width: 60%; vertical-align: top;">
                        <div class="brand-mark">NW</div>
                        <div class="brand-name">NinoWorld Invoice</div>
                        <div class="hero-note">A polished commercial document generated from the verified order and payment lifecycle.</div>
                    </td>
                    <td style="width: 40%; vertical-align: top; text-align: right;">
                        <div class="invoice-chip">{{ $invoice->status->label() }}</div>
                        <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                        <div class="hero-meta">
                            Order {{ $order->reference_number }}<br>
                            Issued {{ $invoice->issued_at?->format('M j, Y H:i') ?? '—' }}<br>
                            Paid {{ $invoice->paid_at?->format('M j, Y H:i') ?? '—' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="grid">
                <tr>
                    <td class="card" style="width: 25%;">
                        <div class="eyebrow">Currency</div>
                        <div class="value">{{ $invoice->currency }}</div>
                    </td>
                    <td class="card" style="width: 25%;">
                        <div class="eyebrow">Payment Method</div>
                        <div class="value">{{ \Illuminate\Support\Str::of($order->payment_method ?: 'not captured')->replace(['_', '-'], ' ')->title() }}</div>
                    </td>
                    <td class="card" style="width: 25%;">
                        <div class="eyebrow">Order Status</div>
                        <div class="value">{{ $order->status->label() }}</div>
                    </td>
                    <td class="card" style="width: 25%;">
                        <div class="eyebrow">Invoice Total</div>
                        <div class="value-mono">{{ number_format((float) $invoice->grand_total, 2) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 0;">
                <tr>
                    <td class="address-box">
                        <div class="address-title">Bill To</div>
                        @if($billingAddress)
                            <div class="address-strong">{{ $billingAddress->first_name }} {{ $billingAddress->last_name }}</div>
                            <div>{{ $billingAddress->address_line_1 }}</div>
                            @if($billingAddress->address_line_2)
                                <div>{{ $billingAddress->address_line_2 }}</div>
                            @endif
                            <div>{{ $billingAddress->city }}, {{ $billingAddress->postal_code }}</div>
                            <div>{{ $billingAddress->country }}</div>
                            @if($billingAddress->phone)
                                <div>{{ $billingAddress->phone }}</div>
                            @endif
                        @else
                            <div>Billing address not captured.</div>
                        @endif
                    </td>
                    <td style="width: 4%;"></td>
                    <td class="address-box">
                        <div class="address-title">Ship To</div>
                        @if($shippingAddress)
                            <div class="address-strong">{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}</div>
                            <div>{{ $shippingAddress->address_line_1 }}</div>
                            @if($shippingAddress->address_line_2)
                                <div>{{ $shippingAddress->address_line_2 }}</div>
                            @endif
                            <div>{{ $shippingAddress->city }}, {{ $shippingAddress->postal_code }}</div>
                            <div>{{ $shippingAddress->country }}</div>
                            @if($shippingAddress->phone)
                                <div>{{ $shippingAddress->phone }}</div>
                            @endif
                        @else
                            <div>Shipping address not captured.</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="items-wrap">
            <table class="items">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th class="align-right">Qty</th>
                        <th class="align-right">Unit</th>
                        <th class="align-right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->lineItems as $item)
                        <tr>
                            <td>
                                <div class="item-name">{{ $item->product_name }}</div>
                                @if($item->variant_name)
                                    <div class="item-sub">{{ $item->variant_name }}</div>
                                @endif
                            </td>
                            <td class="value-mono">{{ $item->sku ?: 'NO-SKU' }}</td>
                            <td class="align-right">{{ $item->quantity }}</td>
                            <td class="align-right value-mono">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="align-right value-mono">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <td class="totals-label">Subtotal</td>
                    <td class="totals-value">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</td>
                </tr>
                <tr>
                    <td class="totals-label">Shipping</td>
                    <td class="totals-value">{{ number_format((float) $invoice->shipping_total, 2) }} {{ $invoice->currency }}</td>
                </tr>
                <tr>
                    <td class="totals-label">Tax</td>
                    <td class="totals-value">{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</td>
                </tr>
                @if((float) $invoice->discount_total > 0)
                    <tr>
                        <td class="totals-label">Discount</td>
                        <td class="totals-value discount">-{{ number_format((float) $invoice->discount_total, 2) }} {{ $invoice->currency }}</td>
                    </tr>
                @endif
                <tr class="grand-row">
                    <td>Grand Total</td>
                    <td class="totals-value">{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div class="footer-rule"></div>
            <div>NinoWorld · invoice {{ $invoice->invoice_number }} · order {{ $order->reference_number }}</div>
            <div style="margin-top: 6px;">This PDF was generated from the platform’s verified order, shipping, and payment records.</div>
        </div>
    </div>
</body>
</html>
