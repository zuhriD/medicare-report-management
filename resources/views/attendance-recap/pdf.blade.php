<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Presensi & Finansial - {{ $recap['period']['month_name'] }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
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
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 9pt;
            color: #64748b;
            margin-top: 2px;
        }
        .meta-right {
            text-align: right;
            font-size: 8.5pt;
            color: #475569;
        }
        
        /* Stats Summary Box */
        .stats-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .stats-grid td {
            padding: 6px 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stats-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            display: block;
        }
        .stats-value {
            font-size: 10.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .stats-value.positive {
            color: #16a34a;
        }
        .stats-value.negative {
            color: #dc2626;
        }

        /* Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 4px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        table.data-table td {
            padding: 5px 4px;
            border: 1px solid #e2e8f0;
            font-size: 8pt;
            text-align: center;
        }
        table.data-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .text-left {
            text-align: left !important;
            padding-left: 6px !important;
        }
        .text-right {
            text-align: right !important;
            padding-right: 6px !important;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-success {
            color: #15803d;
            font-weight: bold;
        }
        .text-danger {
            color: #b91c1c;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: bold;
        }
        .badge-office {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        /* Footer */
        .footer {
            margin-top: 15px;
            font-size: 7.5pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
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
                    <div class="title">Laporan Rekapitulasi Presensi & Finansial</div>
                    <div class="subtitle">Periode: {{ $recap['period']['period_label'] ?? $recap['period']['month_name'] }} | Hari Kerja Efektif: {{ $recap['period']['working_days'] }} Hari</div>
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
                <span class="stats-value">{{ $recap['summary']['total_staff'] }} Orang</span>
            </td>
            <td>
                <span class="stats-label">Tingkat Kehadiran</span>
                <span class="stats-value">{{ $recap['summary']['average_attendance_rate'] }}%</span>
            </td>
            <td>
                <span class="stats-label">Total Uang Hadir</span>
                <span class="stats-value">Rp {{ number_format($recap['summary']['total_regular_allowance'], 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="stats-label">Total Uang Lembur</span>
                <span class="stats-value">Rp {{ number_format($recap['summary']['total_overtime_allowance'], 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="stats-label">Total Net Allowance</span>
                <span class="stats-value positive">Rp {{ number_format($recap['summary']['total_net_allowance'], 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="stats-label">Total Denda (Potong Gaji)</span>
                <span class="stats-value negative">Rp {{ number_format($recap['summary']['total_fine'], 0, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    <!-- Main Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th class="text-left" style="width: 130px;">Nama Staff</th>
                <th style="width: 90px;">Kantor</th>
                <th style="width: 40px;">Hadir</th>
                <th style="width: 40px;">Izin</th>
                <th style="width: 40px;">Alpha</th>
                <th style="width: 45px;">Rate %</th>
                <th style="width: 55px;">Jam Kerja</th>
                <th style="width: 55px;">Jam Lembur</th>
                <th class="text-right" style="width: 75px;">Uang Hadir</th>
                <th class="text-right" style="width: 75px;">Uang Lembur</th>
                <th class="text-right" style="width: 85px;">Net Allowance</th>
                <th class="text-right" style="width: 75px;">Denda (Gaji)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recap['staff_data'] as $index => $staff)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left font-bold">{{ $staff['name'] }}</td>
                    <td><span class="badge badge-office">{{ $staff['office_name'] }}</span></td>
                    <td>{{ $staff['present_days'] }}</td>
                    <td>{{ $staff['leave_days'] }}</td>
                    <td>{{ $staff['alpha_days'] }}</td>
                    <td class="font-bold">{{ $staff['attendance_rate'] }}%</td>
                    <td>{{ $staff['working_hours'] }}h</td>
                    <td>{{ $staff['overtime_hours'] }}h</td>
                    <td class="text-right">Rp {{ number_format($staff['regular_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($staff['overtime_allowance'], 0, ',', '.') }}</td>
                    <td class="text-right font-bold text-success">
                        Rp {{ number_format($staff['net_allowance'], 0, ',', '.') }}
                    </td>
                    <td class="text-right {{ $staff['total_fine'] > 0 ? 'text-danger' : '' }}">
                        Rp {{ number_format($staff['total_fine'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align: center; padding: 15px; color: #64748b;">
                        Tidak ada data presensi staff untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="3" class="text-left font-bold">TOTAL KESELURUHAN</td>
                <td>{{ $recap['summary']['total_present'] }}</td>
                <td>{{ $recap['summary']['total_leaves'] }}</td>
                <td>{{ $recap['summary']['total_alpha'] }}</td>
                <td>{{ $recap['summary']['average_attendance_rate'] }}%</td>
                <td>{{ $recap['summary']['total_working_hours'] }}h</td>
                <td>{{ $recap['summary']['total_overtime_hours'] }}h</td>
                <td class="text-right">Rp {{ number_format($recap['summary']['total_regular_allowance'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($recap['summary']['total_overtime_allowance'], 0, ',', '.') }}</td>
                <td class="text-right text-success" style="font-size: 9pt;">
                    Rp {{ number_format($recap['summary']['total_net_allowance'], 0, ',', '.') }}
                </td>
                <td class="text-right text-danger">Rp {{ number_format($recap['summary']['total_fine'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td>*Catatan: Net Allowance = Uang Hadir + Uang Lembur. Denda presensi dipotong langsung dari Gaji Pokok karyawan.</td>
                <td style="text-align: right;">Halaman 1 dari 1</td>
            </tr>
        </table>
    </div>

</body>
</html>
