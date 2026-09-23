<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .recap-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
        }

        .dark .recap-card {
            background: #1f2937;
            border-color: #374151;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }

        .recap-stat-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            padding: 1rem;
        }

        .dark .recap-stat-card {
            background: #1f2937;
            border-color: #374151;
        }

        .recap-badge-sky {
            background-color: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .dark .recap-badge-sky {
            background-color: rgba(3, 105, 161, 0.25);
            color: #7dd3fc;
            border-color: #0284c7;
        }

        .recap-badge-emerald {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .dark .recap-badge-emerald {
            background-color: rgba(6, 78, 59, 0.25);
            color: #6ee7b7;
            border-color: #065f46;
        }

        .recap-badge-amber {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .dark .recap-badge-amber {
            background-color: rgba(120, 53, 15, 0.25);
            color: #fcd34d;
            border-color: #92400e;
        }

        .recap-badge-rose {
            background-color: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        .dark .recap-badge-rose {
            background-color: rgba(190, 18, 60, 0.25);
            color: #fda4af;
            border-color: #9f1239;
        }

        .recap-select {
            width: 100%;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            color: #111827;
            padding: 0.5rem 0.75rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            outline: none;
        }

        .dark .recap-select {
            background-color: #374151;
            border-color: #4b5563;
            color: #ffffff;
        }

        .recap-select:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
        }

        .recap-input {
            width: 100%;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            color: #111827;
            padding: 0.5rem 0.75rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            outline: none;
        }

        .dark .recap-input {
            background-color: #374151;
            border-color: #4b5563;
            color: #ffffff;
        }

        .recap-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
        }

        .recap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .recap-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.75rem 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .dark .recap-table th {
            background-color: #1e293b;
            color: #94a3b8;
            border-color: #334155;
        }

        .recap-table td {
            padding: 0.75rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .recap-table td {
            border-color: #334155;
        }

        .recap-table tr:hover {
            background-color: #f8fafc;
        }

        .dark .recap-table tr:hover {
            background-color: #1e293b;
        }
    </style>

    @php
        $recap = $this->recap;
        $summary = $recap['summary'];
        $period = $recap['period'];
        $staffData = $recap['staff_data'];
    @endphp

    <div class="space-y-6">
        <!-- Filter Toolbar Card -->
        <div class="recap-card">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <!-- Filters Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 flex-1">
                    <!-- Preset Periode -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 dark:text-gray-400">Tipe Periode</label>
                        <select wire:model.live="periodType" class="recap-select">
                            <option value="monthly">Bulan Penuh (1 - Akhir)</option>
                            <option value="cutoff_16_15">Cut-Off (16 - 15)</option>
                            <option value="cutoff_21_20">Cut-Off (21 - 20)</option>
                            <option value="custom">Custom Rentang Tanggal</option>
                        </select>
                    </div>

                    @if ($periodType !== 'custom')
                        <!-- Month Selector -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 dark:text-gray-400">Bulan Referensi</label>
                            <select wire:model.live="selectedMonth" class="recap-select">
                                @foreach ($this->months as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year Selector -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 dark:text-gray-400">Tahun Referensi</label>
                            <select wire:model.live="selectedYear" class="recap-select">
                                @foreach ($this->years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <!-- Custom Start Date -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 dark:text-gray-400">Tanggal Mulai</label>
                            <input type="date" wire:model.live="startDate" class="recap-input">
                        </div>

                        <!-- Custom End Date -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 dark:text-gray-400">Tanggal Selesai</label>
                            <input type="date" wire:model.live="endDate" class="recap-input">
                        </div>
                    @endif

                    <!-- Office Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 dark:text-gray-400">Kantor</label>
                        <select wire:model.live="selectedOfficeId" class="recap-select">
                            <option value="">Semua Kantor</option>
                            @foreach ($this->offices as $office)
                                <option value="{{ $office->id }}">{{ $office->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Staff Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 dark:text-gray-400">Staff</label>
                        <select wire:model.live="selectedUserId" class="recap-select">
                            <option value="">Semua Staff</option>
                            @foreach ($this->users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Export Action Buttons -->
                <div class="flex items-center gap-2 pt-2 xl:pt-0">
                    <a href="{{ $this->pdfExportUrl }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </a>

                    <a href="{{ $this->csvExportUrl }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export CSV
                    </a>
                </div>
            </div>

            <!-- Active Date Info Badge -->
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-gray-500 dark:text-gray-400 gap-2">
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Periode Aktif:</span> 
                    <span class="ml-1 text-sky-600 dark:text-sky-400 font-bold">{{ $period['period_label'] }}</span>
                    <span class="ml-2 text-gray-400">({{ $period['working_days'] }} Hari Kerja Efektif)</span>
                </div>
                <div>
                    Filter Kantor: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $this->selectedOfficeId ? \App\Models\Office::find($this->selectedOfficeId)?->name : 'Semua Cabang' }}</span>
                </div>
            </div>
        </div>

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="recap-stat-card">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Staff</div>
                <div class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $summary['total_staff'] }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $period['working_days'] }} hari kerja</div>
            </div>

            <div class="recap-stat-card">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kehadiran</div>
                <div class="mt-1 text-xl font-bold text-sky-600 dark:text-sky-400">{{ $summary['average_attendance_rate'] }}%</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $summary['total_present'] }} total hari hadir</div>
            </div>

            <div class="recap-stat-card">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Uang Hadir</div>
                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($summary['total_regular_allowance'], 0, ',', '.') }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $summary['total_working_hours'] }} jam kerja</div>
            </div>

            <div class="recap-stat-card">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Uang Lembur</div>
                <div class="mt-1 text-lg font-bold text-amber-600 dark:text-amber-400">Rp {{ number_format($summary['total_overtime_allowance'], 0, ',', '.') }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $summary['total_overtime_hours'] }} jam lembur</div>
            </div>

            <div class="recap-stat-card">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Denda (Gaji)</div>
                <div class="mt-1 text-lg font-bold text-rose-600 dark:text-rose-400">Rp {{ number_format($summary['total_fine'], 0, ',', '.') }}</div>
                <div class="text-xs text-gray-400 mt-0.5">Potongan gaji pokok ({{ $summary['total_alpha'] }} alpha, {{ $summary['total_leaves'] }} izin)</div>
            </div>

            <div class="recap-stat-card" style="border-color: #a7f3d0; background-color: #f0fdf4;">
                <div class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Net Allowance</div>
                <div class="mt-1 text-lg font-bold text-emerald-700">Rp {{ number_format($summary['total_net_allowance'], 0, ',', '.') }}</div>
                <div class="text-xs text-emerald-600 mt-0.5">Hadir + Lembur (tanpa potong denda)</div>
            </div>
        </div>

        <!-- Main Recap Table Card -->
        <div class="recap-card" style="padding: 0; overflow: hidden;">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-gray-900 text-sm tracking-wide dark:text-white uppercase">
                    Rincian Presensi & Tunjangan Staff ({{ $period['period_label'] }})
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Menampilkan {{ count($staffData) }} staff
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="recap-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Kantor</th>
                            <th style="text-align: center;">Hadir</th>
                            <th style="text-align: center;">Izin</th>
                            <th style="text-align: center;">Alpha</th>
                            <th style="text-align: center;">Kehadiran</th>
                            <th style="text-align: right;">Jam Kerja</th>
                            <th style="text-align: right;">Jam OT</th>
                            <th style="text-align: right;">Uang Hadir</th>
                            <th style="text-align: right;">Uang OT</th>
                            <th style="text-align: right;">Denda</th>
                            <th style="text-align: right;">Net Allowance</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($staffData as $staff)
                            <tr>
                                <td>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $staff['name'] }}</div>
                                    <div class="text-xs text-gray-400">{{ $staff['email'] }}</div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold recap-badge-sky">
                                        {{ $staff['office_name'] }}
                                    </span>
                                </td>
                                <td style="text-align: center; font-weight: 600;" class="text-gray-800 dark:text-gray-200">
                                    {{ $staff['present_days'] }}
                                </td>
                                <td style="text-align: center; font-weight: 600;" class="text-amber-600 dark:text-amber-400">
                                    {{ $staff['leave_days'] }}
                                </td>
                                <td style="text-align: center; font-weight: 600;" class="text-rose-600 dark:text-rose-400">
                                    {{ $staff['alpha_days'] }}
                                </td>
                                <td style="text-align: center; font-weight: 700;" class="{{ $staff['attendance_rate'] >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $staff['attendance_rate'] }}%
                                </td>
                                <td style="text-align: right;" class="text-gray-600 dark:text-gray-400">
                                    {{ $staff['working_hours'] }}h
                                </td>
                                <td style="text-align: right;" class="text-gray-600 dark:text-gray-400">
                                    {{ $staff['overtime_hours'] }}h
                                </td>
                                <td style="text-align: right; font-weight: 600;" class="text-gray-900 dark:text-gray-200">
                                    Rp {{ number_format($staff['regular_allowance'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 600;" class="text-gray-900 dark:text-gray-200">
                                    Rp {{ number_format($staff['overtime_allowance'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 600;" class="{{ $staff['total_fine'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400' }}">
                                    Rp {{ number_format($staff['total_fine'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 700;" class="{{ $staff['net_allowance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    Rp {{ number_format($staff['net_allowance'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: center;">
                                    <button wire:click="openDetailModal({{ $staff['user_id'] }})" type="button" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" style="text-align: center; padding: 2rem; color: #94a3b8;">
                                    Tidak ada data presensi staff untuk filter periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8fafc; font-weight: bold; border-top: 2px solid #cbd5e1;" class="dark:bg-gray-800 text-xs text-gray-900 dark:text-white">
                            <td colspan="2" style="text-transform: uppercase;">Total Keseluruhan</td>
                            <td style="text-align: center;">{{ $summary['total_present'] }}</td>
                            <td style="text-align: center;">{{ $summary['total_leaves'] }}</td>
                            <td style="text-align: center;">{{ $summary['total_alpha'] }}</td>
                            <td style="text-align: center;">{{ $summary['average_attendance_rate'] }}%</td>
                            <td style="text-align: right;">{{ $summary['total_working_hours'] }}h</td>
                            <td style="text-align: right;">{{ $summary['total_overtime_hours'] }}h</td>
                            <td style="text-align: right;">Rp {{ number_format($summary['total_regular_allowance'], 0, ',', '.') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($summary['total_overtime_allowance'], 0, ',', '.') }}</td>
                            <td style="text-align: right;" class="text-rose-600 dark:text-rose-400">Rp {{ number_format($summary['total_fine'], 0, ',', '.') }}</td>
                            <td style="text-align: right; font-size: 0.9375rem;" class="text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($summary['total_net_allowance'], 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Drilldown Detail Harian Staff -->
    @if ($showDetailModal && $selectedStaffDetail)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-4xl border border-gray-200 dark:border-gray-700 overflow-hidden my-8">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/70 dark:bg-gray-750">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Rincian Presensi Harian: {{ $selectedStaffDetail['name'] }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Kantor: {{ $selectedStaffDetail['office_name'] }} | Periode: {{ $period['period_label'] }}
                        </p>
                    </div>
                    <button wire:click="closeDetailModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 max-h-[65vh] overflow-y-auto space-y-4">
                    <!-- Quick Stats Bar -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-200 dark:border-gray-600 text-center">
                            <div class="text-xs text-gray-500 uppercase">Hadir</div>
                            <div class="font-bold text-base text-gray-900 dark:text-white">{{ $selectedStaffDetail['present_days'] }} / {{ $selectedStaffDetail['working_days'] }} Hari</div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-200 dark:border-gray-600 text-center">
                            <div class="text-xs text-gray-500 uppercase">Durasi Kerja</div>
                            <div class="font-bold text-base text-gray-900 dark:text-white">{{ $selectedStaffDetail['working_hours'] }} Jam</div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-200 dark:border-gray-600 text-center">
                            <div class="text-xs text-gray-500 uppercase">Total Denda</div>
                            <div class="font-bold text-base text-rose-600">Rp {{ number_format($selectedStaffDetail['total_fine'], 0, ',', '.') }}</div>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800 text-center">
                            <div class="text-xs text-emerald-700 uppercase">Net Allowance</div>
                            <div class="font-bold text-base text-emerald-600">Rp {{ number_format($selectedStaffDetail['net_allowance'], 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <!-- Daily Table Breakdown -->
                    <table class="recap-table" style="font-size: 0.8125rem; border: 1px solid #e2e8f0; border-radius: 0.5rem;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Waktu Masuk & Pulang</th>
                                <th style="text-align: right;">Durasi Kerja</th>
                                <th style="text-align: right;">Uang Hadir</th>
                                <th style="text-align: right;">Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selectedStaffDetail['daily_breakdown'] as $dateStr => $day)
                                <tr class="{{ $day['status'] === 'holiday' ? 'opacity-60 bg-gray-50/50 dark:bg-gray-800/50' : '' }}">
                                    <td class="font-semibold text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}
                                        <span class="text-gray-400 text-[11px] font-normal">({{ $day['day_name'] }})</span>
                                    </td>
                                    <td>
                                        @if ($day['status'] === 'present')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold recap-badge-emerald">
                                                Hadir
                                            </span>
                                        @elseif ($day['status'] === 'leave')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold recap-badge-amber">
                                                {{ $day['status_label'] }}
                                            </span>
                                        @elseif ($day['status'] === 'alpha')
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold recap-badge-rose">
                                                Alpha
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[11px] text-gray-500">
                                                Libur
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-gray-600 dark:text-gray-400">
                                        @if (!empty($day['check_in_at']))
                                            {{ $day['check_in_at'] }} - {{ $day['check_out_at'] ?? 'Belum Pulang' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="text-align: right;" class="text-gray-700 dark:text-gray-300">
                                        {{ $day['working_minutes'] > 0 ? round($day['working_minutes'] / 60, 1) . ' jam' : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600;" class="text-gray-900 dark:text-white">
                                        {{ $day['regular_allowance'] > 0 ? 'Rp ' . number_format($day['regular_allowance'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600;" class="{{ $day['fine_amount'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400' }}">
                                        {{ $day['fine_amount'] > 0 ? 'Rp ' . number_format($day['fine_amount'], 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 flex justify-end">
                    <button wire:click="closeDetailModal" type="button" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>