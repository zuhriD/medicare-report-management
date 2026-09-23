<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Permohonan Izin - {{ $leave->user?->name ?? 'Staff' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 20mm 20mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Kop Surat */
        .kop-surat {
            border-bottom: 3px double #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
            text-align: center;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .company-subtitle {
            font-size: 9pt;
            color: #475569;
            margin-bottom: 2px;
        }

        .company-address {
            font-size: 8.5pt;
            color: #64748b;
        }

        /* Document Title */
        .doc-title-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .doc-number {
            font-size: 9pt;
            color: #475569;
        }

        /* Letter Meta */
        .letter-meta {
            width: 100%;
            margin-bottom: 15px;
            font-size: 9.5pt;
        }

        .letter-meta td {
            vertical-align: top;
            padding: 2px 0;
        }

        /* Paragraphs */
        .content-p {
            margin-bottom: 12px;
            text-align: justify;
        }

        /* Data Tables */
        .info-table {
            width: 100%;
            margin: 10px 0 16px 0;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 9.5pt;
        }

        .info-table td.label-col {
            width: 160px;
            color: #334155;
            font-weight: bold;
        }

        .info-table td.colon-col {
            width: 15px;
            text-align: center;
        }

        .info-table td.val-col {
            color: #0f172a;
        }

        /* Highlight Card Box */
        .card-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0284c7;
            padding: 10px 14px;
            margin: 14px 0;
            border-radius: 4px;
        }

        .card-box-title {
            font-weight: bold;
            font-size: 9pt;
            color: #0369a1;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .badge-approved {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Signatures */
        .signatures {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 9.5pt;
        }

        .sig-space {
            height: 65px;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }

        .sig-title {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 2px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-size: 8pt;
            color: #94a3b8;
            width: 100%;
        }

        .footer table {
            width: 100%;
        }
    </style>
</head>

<body>

    <!-- Kop Surat -->
    <div class="kop-surat">
        <div class="company-name">MEDICARE REPORT MANAGEMENT</div>
        <div class="company-subtitle">Sistem Manajemen Presensi & Laporan Kinerja Karyawan</div>
        <div class="company-address">{{ $leave->office?->name ?? 'Kantor Utama' }} &bull; Tanggal Dokumen: {{ now()->translatedFormat('d F Y') }}</div>
    </div>

    <!-- Judul Surat -->
    <div class="doc-title-container">
        <div class="doc-title">SURAT PERMOHONAN IZIN / CUTI KERJA</div>
        <div class="doc-number">Nomor: {{ 'LR/' . ($leave->created_at ? $leave->created_at->format('Y/m') : date('Y/m')) . '/' . str_pad($leave->id, 4, '0', STR_PAD_LEFT) }}</div>
    </div>

    <!-- Penerima Surat -->
    <table class="letter-meta">
        <tr>
            <td style="width: 80px;"><strong>Kepada Yth.</strong></td>
            <td>: <strong>Dr. Adnan</strong> / Pimpinan Manajemen</td>
        </tr>
        <tr>
            <td><strong>Lokasi</strong></td>
            <td>: {{ $leave->office?->name ?? 'Kantor Medicare' }}</td>
        </tr>
    </table>

    <div class="content-p">
        Dengan hormat,<br>
        Saya yang bertanda tangan di bawah ini:
    </div>

    <!-- Data Pemohon -->
    <table class="info-table">
        <tr>
            <td class="label-col">Nama Karyawan</td>
            <td class="colon-col">:</td>
            <td class="val-col"><strong>{{ $leave->user?->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">Email / Kontak</td>
            <td class="colon-col">:</td>
            <td class="val-col">{{ $leave->user?->email ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label-col">Unit / Kantor</td>
            <td class="colon-col">:</td>
            <td class="val-col">{{ $leave->office?->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="content-p">
        Dengan ini bermaksud untuk mengajukan permohonan <strong>{{ match($leave->leave_type) {
            'sick' => 'Izin Sakit (Sick Leave)',
            'permission' => 'Izin Keperluan Khusus',
            'annual_leave' => 'Cuti Tahunan (Annual Leave)',
            default => 'Izin Tidak Masuk Kerja'
        } }}</strong> dengan rincian pelaksanaan sebagai berikut:
    </div>

    <!-- Rincian Izin -->
    <table class="info-table">
        <tr>
            <td class="label-col">Periode Izin</td>
            <td class="colon-col">:</td>
            <td class="val-col">
                <strong>
                    {{ \Carbon\Carbon::parse($leave->start_date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                    @if ($leave->start_date->ne($leave->end_date))
                    s/d {{ \Carbon\Carbon::parse($leave->end_date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                    @endif
                </strong>
            </td>
        </tr>
        <tr>
            <td class="label-col">Total Hari Kerja</td>
            <td class="colon-col">:</td>
            <td class="val-col"><strong>{{ $leave->total_days }} Hari Kerja</strong> (tidak termasuk hari libur/Minggu)</td>
        </tr>
        <tr>
            <td class="label-col">Alasan / Keperluan</td>
            <td class="colon-col">:</td>
            <td class="val-col"><em>{{ $leave->reason }}</em></td>
        </tr>
    </table>

    <!-- Kotak Status & Review Supervisor -->
    <div class="card-box">
        <div class="card-box-title">Informasi Verifikasi & Kebijakan Denda</div>
        <table style="width: 100%; font-size: 8.5pt;">
            <tr>
                <td style="width: 150px; color: #475569;">Status Permohonan</td>
                <td style="width: 10px;">:</td>
                <td>
                    @if ($leave->status === 'approved')
                    <span class="badge badge-approved">DISETUJUI (APPROVED)</span>
                    @elseif ($leave->status === 'rejected')
                    <span class="badge badge-rejected">DITOLAK (REJECTED)</span>
                    @else
                    <span class="badge badge-pending">MENUNGGU PERSETUJUAN (PENDING)</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="color: #475569;">Estimasi Denda Normal</td>
                <td>:</td>
                <td>Rp {{ number_format($leave->normal_fine_amount, 0, ',', '.') }}</td>
            </tr>
            @if ($leave->status === 'approved')
            <tr>
                <td style="color: #475569;"><strong>Denda Disetujui (Keringanan)</strong></td>
                <td>:</td>
                <td><strong>Rp {{ number_format($leave->adjusted_fine_amount, 0, ',', '.') }}</strong> {{ $leave->adjusted_fine_amount == 0 ? '(Bebas Denda)' : '' }}</td>
            </tr>
            @if ($leave->approver)
            <tr>
                <td style="color: #475569;">Disetujui Oleh</td>
                <td>:</td>
                <td>{{ $leave->approver->name }} ({{ $leave->approved_at ? $leave->approved_at->format('d/m/Y H:i') : '-' }})</td>
            </tr>
            @endif
            @if ($leave->approval_notes)
            <tr>
                <td style="color: #475569;">Catatan Persetujuan</td>
                <td>:</td>
                <td>{{ $leave->approval_notes }}</td>
            </tr>
            @endif
            @elseif ($leave->status === 'rejected' && $leave->approval_notes)
            <tr>
                <td style="color: #475569;">Alasan Penolakan</td>
                <td>:</td>
                <td>{{ $leave->approval_notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="content-p">
        Saya akan memastikan pekerjaan dan tanggung jawab yang perlu diselesaikan telah dikoordinasikan dengan baik agar operasional pekerjaan tetap berjalan lancar.
    </div>

    <div class="content-p">
        Demikian permohonan ini saya sampaikan. Atas perhatian, kebijaksanaan, dan izin yang diberikan, saya mengucapkan terima kasih.
    </div>

    <!-- Tanda Tangan -->
    <table class="signatures">
        <tr>
            <td>
                <div>Pemohon,</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $leave->user?->name ?? 'Staff' }}</div>
                <div class="sig-title">Staff / Karyawan</div>
            </td>
            <td>
                <div>Mengetahui & Menyetujui,</div>
                <div class="sig-space"></div>
                <!-- <div class="sig-name">{{ $leave->approver?->name ?? 'Dr. Adnan' }}</div> -->
                <div class="sig-name">{{ 'Dr. Adnan Sulaiman' }}</div>
                <div class="sig-title">{{ $leave->approver ? 'Atasan / Supervisor' : 'Pimpinan / Manajemen' }}</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <table>
            <tr>
                <td>Dokumen resmi ini digenerate oleh Medicare Report Management System &bull; {{ date('d/m/Y H:i') }} WIB</td>
                <td style="text-align: right;">ID Pengajuan: #{{ $leave->id }}</td>
            </tr>
        </table>
    </div>

</body>

</html>