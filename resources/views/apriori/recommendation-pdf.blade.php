<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekomendasi Pola Belanja</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 0;
        }

        .header {
            margin-bottom: 14px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
            color: #be123c;
        }

        .meta {
            font-size: 11px;
            color: #475569;
            margin: 2px 0;
        }

        .block {
            margin-bottom: 14px;
        }

        .block-title {
            padding: 8px 10px;
            background: #ffe4e6;
            border: 1px solid #fecdd3;
            font-weight: 700;
            color: #9f1239;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
        }

        .text-center {
            text-align: center;
        }

        .empty {
            border: 1px solid #e2e8f0;
            padding: 10px;
            color: #64748b;
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Laporan Rekomendasi Pola Belanja</h1>
        <p class="meta">Min Support: {{ $minSupport !== null ? number_format((float) $minSupport, 1) . '%' : '-' }}</p>
        <p class="meta">Min Confidence: {{ $minConfidence !== null ? number_format((float) $minConfidence, 1) . '%' : '-' }}</p>
        <p class="meta">Dibuat: {{ $generatedAt->format('d-m-Y H:i') }}</p>
    </div>

    @if (! $hasProcessed)
        <div class="empty">Data belum diproses. Silakan proses Apriori terlebih dahulu dari dashboard.</div>
    @elseif (! $hasData)
        <div class="empty">Data transaksi belum tersedia.</div>
    @elseif ($rulesByItemsetSize->isEmpty())
        <div class="empty">Belum ada aturan yang memenuhi parameter minimum.</div>
    @else
        @foreach ($rulesByItemsetSize as $itemsetSize => $rules)
            <div class="block">
                <div class="block-title">Aturan Asosiasi Itemset {{ $itemsetSize }}</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">No</th>
                            <th>Rekomendasi Pola Belanja</th>
                            <th style="width: 90px;">Support</th>
                            <th style="width: 95px;">Confidence</th>
                            <th style="width: 80px;">Lift Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rules->values() as $index => $rule)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    Jika Konsumen Membeli {{ $rule['antecedent'] }}, Maka Konsumen Juga Akan Membeli {{ $rule['consequent'] }}
                                </td>
                                <td>{{ number_format((float) $rule['support'], 2) }}%</td>
                                <td>{{ number_format((float) $rule['confidence'], 2) }}%</td>
                                <td>{{ number_format((float) ($rule['lift'] ?? 0), 3) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
</body>
</html>
