<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Staff - {{ $staff['name'] }} - {{ $recap['period']['period_label'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 12mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* Header Document */
        .header {
            border-bottom: 2px solid #0284c7;
            padding-bottom: 8px;
            margin-bottom: 10px;
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

        /* Staff Identity Card Box */
        .staff-info-box {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 10px;
            margin-bottom: 12px;
        }
        .staff-info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .staff-info-box td {
            font-size: 8.5pt;
            padding: 2px 4px;
            vertical-align: top;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
            width: 110px;
        }
        .info-value {
            color: #0f172a;
            font-weight: bold;
        }

        /* Section Headings */
        .section-header {
            margin-top: 14px;
            margin-bottom: 6px;
            padding: 4px 8px;
            background-color: #f1f5f9;
            border-left: 3.5px solid #0284c7;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.3px;
        }
        .section-header.overtime {
            border-left-color: #d97706;
            background-color: #fef3c7;
            color: #92400e;
        }
        .section-header.summary {
            border-left-color: #059669;
            background-color: #ecfdf5;
            color: #065f46;
        }

        /* Data Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            margin-bottom: 8px;
        }
        table.data-table th {
            background-color: #f8fafc;
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
            background-color: #fafbfc;
        }
        .row-holiday {
            background-color: #f8fafc !important;
            color: #64748b;
        }
        .row-leave {
            background-color: #fffbeb !important;
        }
        .row-alpha {
            background-color: #fff1f2 !important;
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
        .text-warning {
            color: #b45309;
            font-weight: bold;
        }
        .text-muted {
            color: #94a3b8;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-present {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-ot {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-leave {
            background-color: #fef9c3;
            color: #854d0e;
        }
        .badge-alpha {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-holiday {
            background-color: #f1f5f9;
            color: #64748b;
        }

        /* Summary Stats Grid (Same as General PDF) */
        .stats-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 8px;
        }
        .stats-grid td {
            padding: 6px 8px;
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
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
        }
        .stats-value.positive {
            color: #16a34a;
        }
        .stats-value.negative {
            color: #dc2626;
        }

        /* Detailed Financial Breakdown Box */
        .financial-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .financial-grid > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0 4px;
        }
        .financial-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #ffffff;
            overflow: hidden;
        }
        .financial-card-header {
            padding: 5px 8px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .financial-card-header.allowance {
            background-color: #ecfdf5;
            color: #047857;
            border-bottom: 1px solid #a7f3d0;
        }
        .financial-card-header.fine {
            background-color: #fff1f2;
            color: #be123c;
            border-bottom: 1px solid #fecdd3;
        }
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        .financial-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .financial-table tr.total-row td {
            font-weight: bold;
            border-top: 1.5px solid #cbd5e1;
            background-color: #f8fafc;
        }

        /* Empty banner */
        .empty-box {
            text-align: center;
            padding: 10px;
            color: #94a3b8;
            font-style: italic;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            font-size: 8pt;
            margin-top: 4px;
            margin-bottom: 6px;
        }

        /* Signature block */
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            page-break-inside: avoid;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            font-size: 8pt;
            vertical-align: top;
        }
        .signature-space {
            height: 40px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }
        .signature-title {
            font-size: 7pt;
            color: #64748b;
        }

        /* Page Footer */
        .footer {
            margin-top: 12px;
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
        .footer table {
            width: 100%;
        }
    </style>
</head>
<body>

    <!-- Document Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="title">Rekapitulasi Presensi & Finansial Staff</div>
                    <div class="subtitle">Medicare Report Management System</div>
                </td>
                <td class="meta-right">
                    <strong>Dokumen Resmi HR & Payroll</strong><br>
                    Dicetak: {{ now()->format('d/m/Y H:i') }} WIB<br>
                    Status: Verified
                </td>
            </tr>
        </table>
    </div>

    <!-- Staff Identity & Period Box -->
    <div class="staff-info-box">
        <table>
            <tr>
                <td class="info-label">Nama Staff</td>
                <td class="info-value">: {{ $staff['name'] }}</td>
                <td class="info-label">Periode</td>
                <td class="info-value">: {{ $recap['period']['period_label'] }}</td>
            </tr>
            <tr>
                <td class="info-label">Email</td>
                <td class="info-value">: {{ $staff['email'] }}</td>
                <td class="info-label">Hari Kerja Efektif</td>
                <td class="info-value">: {{ $recap['period']['working_days'] }} Hari</td>
            </tr>
            <tr>
                <td class="info-label">Kantor Utama</td>
                <td class="info-value">: {{ $staff['office_name'] }}</td>
                <td class="info-label">Tingkat Hadir</td>
                <td class="info-value">: {{ $staff['attendance_rate'] }}% ({{ $staff['present_days'] }} Hari Hadir)</td>
            </tr>
        </table>
    </div>

    <!-- ========================================== -->
    <!-- SECTION 1: REKAPITULASI PRESENSI HARIAN   -->
    <!-- ========================================== -->
    <div class="section-header">
        1. Rekapitulasi Presensi Harian (Attendance Recap)
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20px;">No</th>
                <th class="text-left" style="width: 85px;">Tanggal</th>
                <th style="width: 75px;">Status</th>
                <th style="width: 65px;">Masuk</th>
                <th style="width: 65px;">Pulang</th>
                <th style="width: 55px;">Durasi</th>
                <th style="width: 55px;">Terlambat</th>
                <th class="text-right" style="width: 70px;">Uang Hadir</th>
                <th class="text-right" style="width: 70px;">Denda</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($staff['daily_breakdown'] as $dateStr => $day)
                @php
                    $rowClass = '';
                    if ($day['status'] === 'holiday') {
                        $rowClass = 'row-holiday';
                    } elseif ($day['status'] === 'leave') {
                        $rowClass = 'row-leave';
                    } elseif ($day['status'] === 'alpha') {
                        $rowClass = 'row-alpha';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>{{ $no++ }}</td>
                    <td class="text-left font-bold">
                        {{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}
                        <span style="font-weight: normal; font-size: 6.5pt; color: #64748b;">({{ substr($day['day_name'], 0, 3) }})</span>
                    </td>
                    <td>
                        @if ($day['status'] === 'present')
                            <span class="badge badge-present">Hadir</span>
                        @elseif ($day['status'] === 'leave')
                            <span class="badge badge-leave">{{ $day['status_label'] }}</span>
                        @elseif ($day['status'] === 'alpha')
                            <span class="badge badge-alpha">Alpha</span>
                        @else
                            <span class="badge badge-holiday">{{ $day['status_label'] }}</span>
                        @endif
                    </td>
                    <td>{{ $day['check_in_at'] ?? '-' }}</td>
                    <td>{{ $day['check_out_at'] ?? '-' }}</td>
                    <td>{{ $day['working_minutes'] > 0 ? round($day['working_minutes'] / 60, 1) . ' jam' : '-' }}</td>
                    <td>
                        @if ($day['is_late'] ?? false)
                            <span class="text-danger font-bold">{{ $day['late_minutes'] }} m</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-right font-bold">
                        {{ $day['regular_allowance'] > 0 ? 'Rp ' . number_format($day['regular_allowance'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right {{ $day['fine_amount'] > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ $day['fine_amount'] > 0 ? 'Rp ' . number_format($day['fine_amount'], 0, ',', '.') : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="2" class="text-left font-bold">TOTAL PRESENSI</td>
                <td>{{ $staff['present_days'] }} Hadir</td>
                <td colspan="2" style="font-size: 7pt; color: #64748b;">{{ $staff['leave_days'] }} Izin, {{ $staff['alpha_days'] }} Alpha</td>
                <td>{{ $staff['working_hours'] }}h</td>
                <td style="color: #64748b;">{{ $staff['late_days'] }}x telat</td>
                <td class="text-right font-bold">Rp {{ number_format($staff['regular_allowance'], 0, ',', '.') }}</td>
                <td class="text-right text-danger">Rp {{ number_format($staff['total_fine'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- ========================================== -->
    <!-- SECTION 2: REKAPITULASI LEMBUR (OVERTIME) -->
    <!-- ========================================== -->
    <div class="section-header overtime">
        2. Rekapitulasi Lembur (Overtime Recap)
    </div>

    @php
        $overtimeDays = collect($staff['daily_breakdown'])->filter(fn($day) => !empty($day['has_overtime']));
    @endphp

    @if ($overtimeDays->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20px;">No</th>
                    <th class="text-left" style="width: 85px;">Tanggal</th>
                    <th class="text-left" style="width: 95px;">Lokasi Cabang</th>
                    <th style="width: 65px;">Masuk OT</th>
                    <th style="width: 65px;">Pulang OT</th>
                    <th style="width: 55px;">Durasi OT</th>
                    <th style="width: 65px;">Kualifikasi</th>
                    <th class="text-right" style="width: 75px;">Uang Lembur</th>
                    <th class="text-left" style="width: 90px;">Catatan Lembur</th>
                </tr>
            </thead>
            <tbody>
                @php $otNo = 1; @endphp
                @foreach ($overtimeDays as $dateStr => $day)
                    <tr>
                        <td>{{ $otNo++ }}</td>
                        <td class="text-left font-bold">
                            {{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}
                            <span style="font-weight: normal; font-size: 6.5pt; color: #64748b;">({{ substr($day['day_name'], 0, 3) }})</span>
                        </td>
                        <td class="text-left font-bold text-success">
                            {{ $day['overtime_office_name'] ?? $staff['office_name'] }}
                        </td>
                        <td>{{ $day['overtime_check_in_at'] ?? '-' }}</td>
                        <td>{{ $day['overtime_check_out_at'] ?? 'Belum Pulang' }}</td>
                        <td class="font-bold text-warning">
                            {{ $day['overtime_hours'] }}h ({{ $day['overtime_minutes'] }}m)
                        </td>
                        <td>
                            @if (!empty($day['overtime_eligible']))
                                <span class="badge badge-present">Memenuhi</span>
                            @else
                                <span class="badge badge-holiday">Tidak Memenuhi</span>
                            @endif
                        </td>
                        <td class="text-right font-bold text-warning">
                            Rp {{ number_format($day['overtime_allowance'], 0, ',', '.') }}
                        </td>
                        <td class="text-left" style="font-size: 7pt; color: #475569;">
                            {{ $day['overtime_notes'] ?: '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #fef3c7; font-weight: bold; color: #92400e;">
                    <td colspan="3" class="text-left font-bold">TOTAL LEMBUR</td>
                    <td colspan="2">{{ $staff['overtime_days'] }} Hari Lembur</td>
                    <td>{{ $staff['overtime_hours'] }}h</td>
                    <td></td>
                    <td class="text-right font-bold">Rp {{ number_format($staff['overtime_allowance'], 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="empty-box">
            Tidak ada catatan aktivitas lembur pada rentang periode ini ({{ $recap['period']['period_label'] }}).
        </div>
    @endif

    <!-- ========================================== -->
    <!-- SECTION 3: SUMMARY PENDAPATAN & FINANSIAL  -->
    <!-- ========================================== -->
    <div class="section-header summary" style="page-break-before: auto;">
        3. Ringkasan Finansial & Pendapatan (Earnings & Allowance Summary)
    </div>

    <!-- Summary Box (Identik seperti di PDF General) -->
    <table class="stats-grid">
        <tr>
            <td>
                <span class="stats-label">Hari Kerja Efektif</span>
                <span class="stats-value">{{ $staff['working_days'] }} Hari</span>
            </td>
            <td>
                <span class="stats-label">Tingkat Kehadiran</span>
                <span class="stats-value positive">{{ $staff['attendance_rate'] }}%</span>
                <div style="font-size: 6.5pt; color: #64748b;">{{ $staff['present_days'] }} hari hadir</div>
            </td>
            <td>
                <span class="stats-label">Total Uang Hadir</span>
                <span class="stats-value">Rp {{ number_format($staff['regular_allowance'], 0, ',', '.') }}</span>
                <div style="font-size: 6.5pt; color: #64748b;">{{ $staff['working_hours'] }} jam kerja</div>
            </td>
            <td>
                <span class="stats-label">Total Uang Lembur</span>
                <span class="stats-value" style="color: #d97706;">Rp {{ number_format($staff['overtime_allowance'], 0, ',', '.') }}</span>
                <div style="font-size: 6.5pt; color: #64748b;">{{ $staff['overtime_days'] }} hari ({{ $staff['overtime_hours'] }}h)</div>
            </td>
            <td style="background-color: #f0fdf4; border-color: #86efac;">
                <span class="stats-label" style="color: #166534;">Net Allowance</span>
                <span class="stats-value positive">Rp {{ number_format($staff['net_allowance'], 0, ',', '.') }}</span>
                <div style="font-size: 6.5pt; color: #166534;">Hadir + Lembur</div>
            </td>
            <td>
                <span class="stats-label">Total Denda (Potong Gaji)</span>
                <span class="stats-value negative">Rp {{ number_format($staff['total_fine'], 0, ',', '.') }}</span>
                <div style="font-size: 6.5pt; color: #64748b;">{{ $staff['alpha_days'] }} alpha, {{ $staff['leave_days'] }} izin</div>
            </td>
        </tr>
    </table>

    <!-- Rincian Finansial Bersih & Potongan Denda -->
    <table class="financial-grid">
        <tbody>
            <tr>
                <!-- Box Kiri: Hak Penerimaan Tunjangan (Allowance) -->
                <td>
                    <div class="financial-card">
                        <div class="financial-card-header allowance">
                            Hak Tunjangan Staff (Allowances)
                        </div>
                        <table class="financial-table">
                            <tr>
                                <td>Uang Kehadiran Kerja ({{ $staff['present_days'] }} hari)</td>
                                <td class="text-right">Rp {{ number_format($staff['regular_allowance'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Uang Lembur Kerja ({{ $staff['overtime_days'] }} hari / {{ $staff['overtime_hours'] }} jam)</td>
                                <td class="text-right">Rp {{ number_format($staff['overtime_allowance'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="total-row">
                                <td style="color: #047857;">TOTAL NET ALLOWANCE DITERIMA</td>
                                <td class="text-right" style="color: #047857; font-size: 8.5pt;">
                                    Rp {{ number_format($staff['net_allowance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>

                <!-- Box Kanan: Potongan Denda Presensi -->
                <td>
                    <div class="financial-card">
                        <div class="financial-card-header fine">
                            Rincian Denda Presensi (Potongan Gaji Pokok)
                        </div>
                        <table class="financial-table">
                            <tr>
                                <td>Denda Alpha / Tanpa Keterangan ({{ $staff['alpha_days'] }} hari)</td>
                                <td class="text-right text-danger">Rp {{ number_format($staff['alpha_fine'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Denda Izin Kerja ({{ $staff['leave_days'] }} hari)</td>
                                <td class="text-right text-danger">Rp {{ number_format($staff['leave_fine'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="total-row">
                                <td style="color: #b91c1c;">TOTAL DENDA DIPOTONG DARI GAJI</td>
                                <td class="text-right text-danger" style="font-size: 8.5pt;">
                                    Rp {{ number_format($staff['total_fine'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 6.8pt; color: #64748b; margin-top: 5px; font-style: italic;">
        *Catatan Payroll: Net Allowance merupakan total hak tunjangan kehadiran dan lembur yang dibayarkan kepada staff. Sanksi/denda presensi diperhitungkan dan dipotong langsung secara terpisah dari Gaji Pokok staff.
    </div>

    <!-- Signatures Block -->
    <table class="signatures">
        <tr>
            <td>
                <div>Karyawan / Staff</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $staff['name'] }}</div>
                <div class="signature-title">Staff {{ $staff['office_name'] }}</div>
            </td>
            <td>
                <div>Diverifikasi Oleh</div>
                <div class="signature-space"></div>
                <div class="signature-name">( HR & Keuangan )</div>
                <div class="signature-title">Staff Payroll / Personalia</div>
            </td>
            <td>
                <div>Disetujui Oleh</div>
                <div class="signature-space"></div>
                <div class="signature-name">( Pimpinan / Lead )</div>
                <div class="signature-title">Branch Manager / Direktur</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <table>
            <tr>
                <td>Laporan Rekapitulasi Presensi & Finansial Staff — Medicare Report Management</td>
                <td style="text-align: right;">Halaman 1</td>
            </tr>
        </table>
    </div>

</body>
</html>
