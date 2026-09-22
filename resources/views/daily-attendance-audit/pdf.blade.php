<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Log Presensi Harian & Audit - {{ $audit['date_formatted'] }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #0284c7;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 2px;
        }
        .meta-right {
            text-align: right;
            font-size: 8pt;
            color: #475569;
        }
        
        /* Stats Summary Box */
        .stats-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .stats-grid td {
            padding: 5px 8px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stats-label {
            font-size: 7pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            display: block;
        }
        .stats-value {
            font-size: 10pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }

        /* Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 4px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        table.data-table td {
            padding: 4px 4px;
            border: 1px solid #e2e8f0;
            font-size: 7.5pt;
            text-align: center;
        }
        table.data-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .text-left {
            text-align: left !important;
            padding-left: 5px !important;
        }
        .text-right {
            text-align: right !important;
            padding-right: 5px !important;
        }
        .font-bold {
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-present { background-color: #ecfdf5; color: #047857; }
        .badge-working { background-color: #f0f9ff; color: #0369a1; }
        .badge-paused { background-color: #fffbeb; color: #b45309; }
        .badge-leave { background-color: #fffbeb; color: #b45309; }
        .badge-alpha { background-color: #fff1f2; color: #be123c; }
        .badge-holiday { background-color: #f1f5f9; color: #64748b; }

        /* Footer */
        .footer {
            margin-top: 12px;
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
        .footer table {
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="title">Laporan Log Presensi Harian & Audit</div>
                    <div class="subtitle">Tanggal: {{ $audit['day_name'] }}, {{ $audit['date_formatted'] }}</div>
                </td>
                <td class="meta-right">
                    <strong>Medicare Report Management</strong><br>
                    Dicetak: {{ now()->format('d/m/Y H:i') }} WIB<br>
                    Filter: {{ $officeName ?? 'Semua Kantor' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Boxes -->
    <table class="stats-grid">
        <tr>
            <td>
                <span class="stats-label">Total Staff</span>
                <span class="stats-value">{{ $audit['summary']['total_staff'] }}</span>
            </td>
            <td>
                <span class="stats-label">Total Masuk</span>
                <span class="stats-value">{{ $audit['summary']['total_checked_in'] }}</span>
            </td>
            <td>
                <span class="stats-label">Sudah Pulang</span>
                <span class="stats-value">{{ $audit['summary']['total_checked_out'] }}</span>
            </td>
            <td>
                <span class="stats-label">Sedang Izin Keluar</span>
                <span class="stats-value">{{ $audit['summary']['total_currently_paused'] }}</span>
            </td>
            <td>
                <span class="stats-label">Izin / Cuti</span>
                <span class="stats-value">{{ $audit['summary']['total_leaves'] }}</span>
            </td>
            <td>
                <span class="stats-label">Alpha</span>
                <span class="stats-value" style="color: #be123c;">{{ $audit['summary']['total_alpha'] }}</span>
            </td>
            <td>
                <span class="stats-label">Total Jam Kerja</span>
                <span class="stats-value">{{ $audit['summary']['total_working_hours'] }}h</span>
            </td>
        </tr>
    </table>

    <!-- Main Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20px;">No</th>
                <th class="text-left" style="width: 110px;">Nama Staff</th>
                <th style="width: 75px;">Kantor</th>
                <th style="width: 75px;">Status</th>
                <th style="width: 45px;">Masuk</th>
                <th style="width: 45px;">Pulang</th>
                <th style="width: 55px;">GPS Masuk</th>
                <th style="width: 55px;">GPS Pulang</th>
                <th style="width: 45px;">Jeda</th>
                <th style="width: 45px;">Durasi</th>
                <th style="width: 45px;">Lembur</th>
                <th class="text-left" style="width: 100px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($audit['staff_logs'] as $index => $staff)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left font-bold">{{ $staff['name'] }}</td>
                    <td>{{ $staff['office_name'] }}</td>
                    <td>
                        <span class="badge badge-{{ $staff['status'] === 'completed' ? 'present' : ($staff['status'] === 'working' ? 'working' : ($staff['status'] === 'paused' ? 'paused' : ($staff['status'] === 'leave' ? 'leave' : ($staff['status'] === 'holiday' ? 'holiday' : 'alpha')))) }}">
                            {{ $staff['status_label'] }}
                        </span>
                    </td>
                    <td>{{ $staff['check_in_at'] ?? '-' }}</td>
                    <td>{{ $staff['check_out_at'] ?? '-' }}</td>
                    <td>
                        @if ($staff['check_in_distance'] !== null)
                            {{ $staff['check_in_distance'] }}m ({{ $staff['check_in_within_radius'] ? 'Valid' : 'Luar' }})
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($staff['check_out_distance'] !== null)
                            {{ $staff['check_out_distance'] }}m ({{ $staff['check_out_within_radius'] ? 'Valid' : 'Luar' }})
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $staff['total_break_minutes'] > 0 ? $staff['total_break_minutes'] . 'm' : '-' }}</td>
                    <td>{{ $staff['working_hours'] > 0 ? $staff['working_hours'] . 'h' : '-' }}</td>
                    <td>{{ $staff['overtime_hours'] > 0 ? $staff['overtime_hours'] . 'h' : '-' }}</td>
                    <td class="text-left">
                        @if ($staff['leave_request'])
                            Izin: {{ $staff['leave_request']['reason'] }}
                        @elseif ($staff['notes'])
                            {{ $staff['notes'] }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="text-align: center; padding: 12px; color: #64748b;">
                        Tidak ada data staff untuk kantor terpilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td>Laporan ini digenerate secara otomatis oleh Sistem Medicare Report & Attendance Management.</td>
                <td style="text-align: right;">Halaman 1 dari 1</td>
            </tr>
        </table>
    </div>

</body>
</html>
