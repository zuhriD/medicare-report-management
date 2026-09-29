<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Pembayaran Tunjangan - {{ $period->name }}</title>
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
        }
        .text-right {
            text-align: right !important;
        }
        .font-semibold {
            font-weight: 600;
        }
        .font-bold {
            font-weight: bold;
        }
        .total-row td {
            background-color: #e2e8f0 !important;
            font-weight: bold;
            border-top: 2px solid #94a3b8 !important;
            font-size: 8.5pt;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        /* Signature area */
        .signatures {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 8.5pt;
        }
        .sign-space {
            height: 45px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <table>
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div class="title">REKAPITULASI PEMBAYARAN TUNJANGAN</div>
                    <div class="subtitle">
                        Periode: <strong>{{ $period->name }}</strong> ({{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }})
                    </div>
                </td>
                <td class="meta-right" style="width: 40%; vertical-align: top;">
                    <div><strong>Kantor:</strong> {{ $officeName }}</div>
                    <div><strong>Dicetak pada:</strong> {{ now()->translatedFormat('d F Y, H:i') }} WIB</div>
                    <div><strong>Dibuat oleh:</strong> {{ $period->creator?->name ?? 'Sistem' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Metrics -->
    <table class="stats-grid">
        <tr>
            <td>
                <span class="stats-label">Total Tagihan (Payable)</span>
                <span class="stats-value">Rp {{ number_format((float) $period->total_amount, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="stats-label">Total Terbayar (Paid)</span>
                <span class="stats-value positive">Rp {{ number_format((float) $period->total_paid_amount, 0, ',', '.') }} ({{ $period->paid_staff_count }} Staff)</span>
            </td>
            <td>
                <span class="stats-label">Total Sisa (Unpaid)</span>
                <span class="stats-value negative">Rp {{ number_format((float) $period->total_unpaid_amount, 0, ',', '.') }} ({{ $period->unpaid_staff_count }} Staff)</span>
            </td>
            <td>
                <span class="stats-label">Total Staff</span>
                <span class="stats-value">{{ $period->periodStaff->count() }} Orang</span>
            </td>
        </tr>
    </table>

    <!-- Main Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th class="text-left" style="width: 140px;">Nama Staff</th>
                <th class="text-left" style="width: 90px;">Kantor</th>
                <th style="width: 50px;">Hari Hadir</th>
                <th class="text-right" style="width: 85px;">Uang Hadir</th>
                <th style="width: 60px;">Lembur (Jam)</th>
                <th class="text-right" style="width: 85px;">Uang Lembur</th>
                <th class="text-right" style="width: 95px;">Grand Total</th>
                <th style="width: 70px;">Status</th>
                <th class="text-left" style="width: 120px;">Info Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($period->periodStaff as $index => $staff)
                @php
                    $otHours = round($staff->total_overtime_minutes / 60, 1);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left font-semibold">
                        {{ $staff->user?->name ?? 'N/A' }}
                    </td>
                    <td class="text-left">{{ $staff->user?->office?->name ?? '-' }}</td>
                    <td>{{ $staff->total_attendance_days }} Hari</td>
                    <td class="text-right">Rp {{ number_format((float) $staff->total_attendance_amount, 0, ',', '.') }}</td>
                    <td>{{ $otHours }} Jam ({{ $staff->total_overtime_minutes }}m)</td>
                    <td class="text-right">Rp {{ number_format((float) $staff->total_overtime_amount, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">
                        Rp {{ number_format((float) $staff->total_allowance, 0, ',', '.') }}
                    </td>
                    <td>
                        @if($staff->isPaid())
                            <span class="badge badge-paid">LUNAS</span>
                        @else
                            <span class="badge badge-unpaid">BELUM</span>
                        @endif
                    </td>
                    <td class="text-left" style="font-size: 7.5pt;">
                        @if($staff->isPaid())
                            <div>{{ $staff->paid_at ? $staff->paid_at->format('d/m/Y') : '' }} ({{ $staff->payment_method ?? 'Transfer' }})</div>
                            @if($staff->payment_reference)
                                <div style="color: #64748b;">Ref: {{ $staff->payment_reference }}</div>
                            @endif
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="padding: 15px; color: #64748b;">Tidak ada data staff pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-left" style="padding-left: 8px;">TOTAL</td>
                <td>{{ $period->periodStaff->sum('total_attendance_days') }} Hari</td>
                <td class="text-right">Rp {{ number_format((float) $period->periodStaff->sum('total_attendance_amount'), 0, ',', '.') }}</td>
                <td>{{ round($period->periodStaff->sum('total_overtime_minutes') / 60, 1) }} Jam</td>
                <td class="text-right">Rp {{ number_format((float) $period->periodStaff->sum('total_overtime_amount'), 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format((float) $period->total_amount, 0, ',', '.') }}</td>
                <td colspan="2" class="text-left" style="font-size: 7.5pt;">
                    Paid: Rp {{ number_format((float) $period->total_paid_amount, 0, ',', '.') }}<br>
                    Unpaid: Rp {{ number_format((float) $period->total_unpaid_amount, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Block -->
    <table class="signatures">
        <tr>
            <td>
                Dibuat Oleh,<br>
                <strong>HR Officer</strong>
                <div class="sign-space"></div>
                ( ______________________ )
            </td>
            <td>
                Diverifikasi Oleh,<br>
                <strong>Finance / Accounting</strong>
                <div class="sign-space"></div>
                ( ______________________ )
            </td>
            <td>
                Disetujui Oleh,<br>
                <strong>Management / Director</strong>
                <div class="sign-space"></div>
                ( ______________________ )
            </td>
        </tr>
    </table>

</body>
</html>
