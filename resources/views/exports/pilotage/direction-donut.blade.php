<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 34px;
        }

        body {
            margin: 0;
            background: #ffffff;
            color: #1c203d;
            font-family: DejaVu Sans, sans-serif;
        }

        h1 {
            margin: 0 0 26px 0;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
        }

        .chart {
            text-align: center;
        }

        .chart img {
            width: 300px;
            height: 300px;
        }

        .legend {
            margin: 28px auto 0 auto;
            width: 88%;
            border-collapse: collapse;
        }

        .legend td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5edf5;
            font-size: 12px;
            vertical-align: middle;
        }

        .legend .value {
            text-align: right;
            font-weight: 700;
        }

        .dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }

        .empty {
            margin: 80px auto 0 auto;
            width: 70%;
            padding: 28px;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            color: #64748b;
            text-align: center;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    @if($total > 0)
        <div class="chart">
            @if(!empty($chartImage))
                <img src="{{ $chartImage }}" alt="Graphique donut">
            @else
                <div class="empty">Graphique indisponible.</div>
            @endif
        </div>

        <table class="legend">
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>
                            <span class="dot" style="background: {{ $row['color'] }}"></span>
                            {{ $row['direction'] }}
                        </td>
                        <td class="value">{{ number_format((int) $row['total'], 0, ',', ' ') }}</td>
                        <td class="value">{{ number_format((float) $row['percent'], 1, ',', ' ') }} %</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">Aucune donnée disponible pour cette période.</div>
    @endif
</body>
</html>

