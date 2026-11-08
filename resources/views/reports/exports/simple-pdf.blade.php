<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 22px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; line-height: 1.35; }
        h1 { font-size: 15px; margin: 0 0 10px; font-weight: 700; }
        h2 { font-size: 11px; margin: 14px 0 6px; font-weight: 700; }
        p { margin: 0 0 5px; }
        ol { margin: 0 0 8px 16px; padding: 0; }
        li { margin-bottom: 4px; }
        .meta { margin-bottom: 8px; }
        .meta div { margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #777; padding: 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        th { font-weight: 700; text-align: left; }
        .num { text-align: right; }
        .w-indicator { width: 28%; }
        .w-code { width: 9%; }
        .w-area { width: 20%; }
        .w-unit { width: 12%; }
        .w-small { width: 9%; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    <div class="meta">
        @foreach ($context as $line)
        <div>{{ $line }}</div>
        @endforeach
    </div>

    <h2>Executive Summary</h2>
    <ol>
        @foreach ($executiveSummary as $sentence)
        <li>{{ $sentence }}</li>
        @endforeach
    </ol>

    <h2>Detailed Results</h2>
    @foreach ($sectionIntro as $paragraph)
    <p>{{ $paragraph }}</p>
    @endforeach

    <table>
        <thead>
            <tr>
                <th class="w-indicator">Indicator</th>
                <th class="w-code">Code</th>
                <th class="w-area">Thematic Area</th>
                <th class="w-unit">Unit</th>
                <th class="w-small num">Target</th>
                <th class="w-small num">Actual</th>
                <th class="w-small num">Achievement %</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tableRows as $row)
            <tr>
                <td>{{ $row[0] ?? '—' }}</td>
                <td>{{ $row[1] ?? '—' }}</td>
                <td>{{ $row[2] ?? '—' }}</td>
                <td>{{ $row[3] ?? '—' }}</td>
                <td class="num">{{ $row[4] ?? '—' }}</td>
                <td class="num">{{ $row[5] ?? '—' }}</td>
                <td class="num">{{ $row[6] ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7">No report rows available.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
