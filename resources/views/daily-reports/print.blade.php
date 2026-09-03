<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report - {{ $date->format('d M Y') }}</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: var(--text-dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
        }

        .no-print-bar {
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .no-print-bar h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #f8fafc;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: #334155;
            color: #f8fafc;
            border-color: #475569;
        }

        .btn-secondary:hover {
            background-color: #475569;
        }

        .container {
            max-width: 900px;
            margin: 24px auto;
            background: #ffffff;
            padding: 36px 40px;
            border-radius: 10px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header-title h1 {
            margin: 0 0 4px 0;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .header-title p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .header-meta {
            text-align: right;
            font-size: 12px;
            color: var(--text-muted);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
            background-color: var(--bg-light);
            padding: 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .stat-card {
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 2px;
        }

        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .report-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .report-card-header {
            background-color: var(--bg-light);
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-primary {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-info {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .report-body {
            padding: 18px;
        }

        .task-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 12px;
            font-size: 12px;
            color: var(--text-muted);
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 8px;
        }

        .task-meta strong {
            color: var(--text-dark);
        }

        .description {
            font-size: 13px;
            color: #334155;
            line-height: 1.7;
        }

        .description p {
            margin: 0 0 10px 0;
        }

        .description ul, .description ol {
            margin: 6px 0 10px 20px;
            padding: 0;
        }

        .description li {
            margin-bottom: 4px;
        }

        .description img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin: 8px 0;
            display: block;
        }

        .images-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .image-item {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            background: #fafafa;
            max-width: 280px;
        }

        .image-item img {
            max-width: 100%;
            max-height: 200px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
            border-radius: 4px;
        }

        .image-caption {
            padding: 6px 8px;
            font-size: 11px;
            color: var(--text-muted);
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            background-color: var(--bg-light);
            border-radius: 8px;
            border: 1px dashed var(--border-color);
        }

        @media print {
            body {
                background-color: #ffffff;
                font-size: 12px;
            }

            .no-print-bar {
                display: none !important;
            }

            .container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }

            .report-card {
                page-break-inside: avoid;
                border-color: #cbd5e1;
            }

            .btn {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    @unless($isPdf)
    <div class="no-print-bar">
        <h2>Daily Report Preview — {{ $date->format('d M Y') }}</h2>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
            <a href="{{ route('daily-reports.print', ['date' => $date->format('Y-m-d'), 'pdf' => 1]) }}" class="btn btn-secondary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Download PDF
            </a>
            <button onclick="window.close()" class="btn btn-secondary">
                Close
            </button>
        </div>
    </div>
    @endunless

    <div class="container">
        <div class="header">
            <div class="header-title">
                <h1>Daily Progress Report</h1>
                <p>{{ $date->format('l, F j, Y') }}</p>
            </div>
            <div class="header-meta">
                <div><strong>Medicare Report System</strong></div>
                <div>Generated on: {{ now()->format('d M Y, H:i') }}</div>
                <div>Generated by: {{ auth()->user()?->name ?? 'System' }}</div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['total_reports'] }}</div>
                <div class="stat-label">Total Reports</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['user_count'] }}</div>
                <div class="stat-label">Developers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['module_count'] }}</div>
                <div class="stat-label">Modules Worked</div>
            </div>
        </div>

        @forelse ($dailyReports as $report)
            <div class="report-card">
                <div class="report-card-header">
                    <div class="user-info">
                        <span class="user-name">{{ $report->user?->name ?? 'Unknown Member' }}</span>
                        @if ($report->user?->sections->isNotEmpty())
                            <span class="badge badge-info">{{ $report->user->sections->pluck('name')->implode(', ') }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="badge badge-primary">{{ $report->subModule?->module?->name ?? 'General' }}</span>
                    </div>
                </div>

                <div class="report-body">
                    <div class="task-meta">
                        <div><strong>Main Task:</strong> {{ $report->subModule?->module?->name ?? '—' }}</div>
                        <div><strong>Sub Task / Platform:</strong> {{ $report->subModule?->name ?? '—' }}</div>
                    </div>

                    <div class="description">
    @php
        $rawDescription = $report->getRawOriginal('description') ?? $report->description;
        $tasks = \App\Models\DailyReport::parseTasks($rawDescription);
    @endphp

    @if (!empty($tasks))
        <ul>
            @foreach ($tasks as $task)
                <li>{{ $task }}</li>
            @endforeach
        </ul>
    @endif
</div>

                    @if ($report->reportImages->isNotEmpty())
                        <div class="images-grid">
                            @foreach ($report->reportImages as $image)
                                <div class="image-item">
                                    <img src="{{ $image->src }}" alt="{{ $image->caption ?? 'Attachment' }}">
                                    @if ($image->caption)
                                        <div class="image-caption">{{ $image->caption }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state">
                <h3>No daily reports found</h3>
                <p>There are no daily reports submitted for {{ $date->format('d M Y') }}.</p>
            </div>
        @endforelse
    </div>

</body>
</html>
