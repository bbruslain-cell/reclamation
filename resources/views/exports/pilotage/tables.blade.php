<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1c203d;
            font-size: 12px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 14px 0;
        }
        h2 {
            font-size: 14px;
            margin: 18px 0 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        th, td {
            border: 1px solid #b8c3cf;
            padding: 6px 8px;
            vertical-align: top;
        }
        th {
            background: #e9eff6;
            font-weight: 700;
            text-align: left;
        }
        .footer-row td {
            font-weight: 700;
            background: #f3f6f9;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    @foreach($sections as $section)
        @if(!empty($section['title']))
            <h2>{{ $section['title'] }}</h2>
        @endif

        <table>
            <thead>
                <tr>
                    @foreach($section['headers'] as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($section['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($section['headers']) }}">Aucune donnee disponible.</td>
                    </tr>
                @endforelse

                @if(!empty($section['footer']))
                    <tr class="footer-row">
                        @foreach($section['footer'] as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
</body>
</html>
