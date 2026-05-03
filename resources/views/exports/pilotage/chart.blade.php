<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            margin: 0;
            background: #f3f7fb;
            color: #1c203d;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h1 {
            margin: 0 0 18px 0;
            text-align: center;
            font-size: 20px;
            line-height: 1.25;
            font-weight: 700;
        }

        .report {
            background: #ffffff;
            border: 1px solid #dce8f3;
            padding-top: 18px;
        }

        .report-body {
            padding: 18px 20px 20px 20px;
        }

        .chart-box {
            padding: 14px 14px 10px 14px;
            background: #fbfdff;
            border: 1px solid #e3edf7;
        }

        .chart {
            text-align: center;
        }

        .chart img {
            width: 96%;
        }

        .legend-title {
            margin: 16px 0 8px 0;
            color: #344054;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .legend {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #e3edf7;
        }

        .legend th {
            padding: 9px 11px;
            background: #eef4fb;
            color: #1c203d;
            font-size: 10px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .legend td {
            padding: 9px 11px;
            border-top: 1px solid #edf2f7;
            color: #344054;
            font-size: 11px;
            vertical-align: middle;
        }

        .legend tbody tr:first-child td {
            border-top: 0;
        }

        .legend td:last-child,
        .legend th:last-child {
            text-align: right;
            font-weight: 700;
        }

        .dot {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }

        .empty {
            margin: 38px auto;
            width: 70%;
            padding: 28px;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            text-align: center;
            font-size: 13px;
        }

        .footer {
            margin-top: 10px;
            color: #98a2b3;
            font-size: 9px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="report">
        <h1>{{ $title }}</h1>

        <div class="report-body">
            <div class="chart-box">
                @if(!empty($chartImage))
                    <div class="chart">
                        <img src="{{ $chartImage }}" alt="{{ $title }}">
                    </div>
                @else
                    <div class="empty">Graphique indisponible.</div>
                @endif
            </div>

            @if(!empty($legendRows))
                <p class="legend-title">Légende</p>
                <table class="legend">
                    <thead>
                        <tr>
                            @foreach($legendHeaders as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($legendRows as $row)
                            <tr>
                                @foreach($row['cells'] as $cellIndex => $cell)
                                    <td>
                                        @if($cellIndex === 0)
                                            <span class="dot" style="background: {{ $row['color'] }}"></span>
                                        @endif
                                        {{ $cell }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

           
        </div>
    </div>
</body>
</html>
