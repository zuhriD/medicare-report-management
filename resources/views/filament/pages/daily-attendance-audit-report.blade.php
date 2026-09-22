<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .att-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
        }

        .dark .att-card {
            background: #1f2937;
            border-color: #374151;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }

        .att-stat-card {
            background: #ffffff;
            border-radius: 0.875rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dark .att-stat-card {
            background: #1f2937;
            border-color: #374151;
        }

        .att-badge-blue {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        .dark .att-badge-blue {
            background-color: rgba(30, 58, 138, 0.3);
            color: #93c5fd;
            border-color: #1e40af;
        }

        .att-badge-emerald {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .dark .att-badge-emerald {
            background-color: rgba(6, 78, 59, 0.3);
            color: #6ee7b7;
            border-color: #065f46;
        }

        .att-badge-amber {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .dark .att-badge-amber {
            background-color: rgba(120, 53, 15, 0.3);
            color: #fcd34d;
            border-color: #92400e;
        }

        .att-badge-rose {
            background-color: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        .dark .att-badge-rose {
            background-color: rgba(136, 19, 55, 0.3);
            color: #fda4af;
            border-color: #9f1239;
        }

        .att-badge-purple {
            background-color: #faf5ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .dark .att-badge-purple {
            background-color: rgba(88, 28, 135, 0.3);
            color: #d8b4fe;
            border-color: #6b21a8;
        }

        .att-badge-gray {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .dark .att-badge-gray {
            background-color: rgba(75, 85, 99, 0.3);
            color: #9ca3af;
            border-color: #4b5563;
        }

        .att-btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-weight: 600;
            border-radius: 0.625rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .att-btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .att-btn-success {
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            font-weight: 600;
            border-radius: 0.625rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .att-btn-success:hover:not(:disabled) {
            background: linear-gradient(135deg, #047857, #065f46);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }

        .att-btn-danger {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #ffffff;
            font-weight: 600;
            border-radius: 0.625rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .att-btn-danger:hover:not(:disabled) {
            background: linear-gradient(135deg, #be123c, #9f1239);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
        }

        .att-btn-warning {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #ffffff;
            font-weight: 600;
            border-radius: 0.625rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .att-btn-warning:hover:not(:disabled) {
            background: linear-gradient(135deg, #b45309, #92400e);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
        }

        .att-btn-secondary {
            background-color: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
            font-weight: 600;
            border-radius: 0.625rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .dark .att-btn-secondary {
            background-color: #374151;
            color: #e5e7eb;
            border-color: #4b5563;
        }

        .att-btn-secondary:hover:not(:disabled) {
            background-color: #f9fafb;
        }

        .dark .att-btn-secondary:hover:not(:disabled) {
            background-color: #4b5563;
        }

        .att-input, .att-select {
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

        .dark .att-input, .dark .att-select {
            background-color: #374151;
            border-color: #4b5563;
            color: #ffffff;
        }

        .att-input:focus, .att-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .att-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            text-align: left;
        }

        .att-table th {
            background-color: #f9fafb;
            color: #4b5563;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .dark .att-table th {
            background-color: #374151;
            color: #9ca3af;
            border-color: #4b5563;
        }

        .att-table td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
            vertical-align: middle;
        }

        .dark .att-table td {
            border-color: #374151;
            color: #f3f4f6;
        }

        .att-table tr:hover td {
            background-color: #f9fafb;
        }

        .dark .att-table tr:hover td {
            background-color: rgba(55, 65, 81, 0.5);
        }

        .thumb-preview {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.375rem;
            object-fit: cover;
            border: 1px solid #d1d5db;
            cursor: pointer;
            transition: transform 0.15s ease-in-out;
        }

        .thumb-preview:hover {
            transform: scale(1.1);
            border-color: #2563eb;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(2px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
    </style>

    @php
        $audit = $this->auditData;
        $summary = $audit['summary'];
    @endphp

    <div class="space-y-6">
        {{-- 1. Header & Filter Bar --}}
        <div class="att-card">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-xl att-badge-blue shrink-0">
                        <x-heroicon-o-clipboard-document-check class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </span>
                    <div>
                        <h2 class="text-base font-extrabold text-gray-900 dark:text-white">
                            Laporan Log Presensi Harian & Audit
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Audit presensi staf, verifikasi selfie kamera, validasi geofence GPS kantor, dan riwayat jeda istirahat.
                        </p>
                    </div>
                </div>

                <!-- Export Action Buttons -->
                <div class="flex items-center gap-2">
                    <a href="{{ $this->pdfExportUrl }}" target="_blank"
                       class="px-3.5 py-2 text-xs font-semibold att-btn-danger gap-1.5 shadow-sm">
                        <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                        <span>Unduh PDF (Landscape)</span>
                    </a>

                    <a href="{{ $this->csvExportUrl }}"
                       class="px-3.5 py-2 text-xs font-semibold att-btn-success gap-1.5 shadow-sm">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                        <span>Unduh CSV / Excel</span>
                    </a>
                </div>
            </div>

            <!-- Controls Filter Grid -->
            <div class="mt-5 pt-4 border-t border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <!-- Date Filter -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Pilih Tanggal Audit
                    </label>
                    <input type="date"
                           wire:model.live="selectedDate"
                           class="att-input" />
                </div>

                <!-- Office Filter -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Filter Kantor
                    </label>
                    <select wire:model.live="selectedOfficeId" class="att-select">
                        <option value="">Semua Kantor</option>
                        @foreach($this->offices as $office)
                            <option value="{{ $office->id }}">{{ $office->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Staff Filter -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        Filter Karyawan
                    </label>
                    <select wire:model.live="selectedUserId" class="att-select">
                        <option value="">Semua Karyawan</option>
                        @foreach($this->users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Navigation Quick Buttons -->
                <div class="flex items-center gap-1.5">
                    <button type="button"
                            wire:click="$set('selectedDate', '{{ \Carbon\Carbon::parse($this->selectedDate ?: now()->toDateString())->subDay()->toDateString() }}')"
                            class="flex-1 px-2.5 py-2 text-xs att-btn-secondary">
                        &larr; Kemarin
                    </button>
                    <button type="button"
                            wire:click="$set('selectedDate', '{{ now()->toDateString() }}')"
                            class="px-3 py-2 text-xs font-semibold att-badge-blue rounded-lg hover:opacity-90 transition">
                        Hari Ini
                    </button>
                    <button type="button"
                            wire:click="$set('selectedDate', '{{ \Carbon\Carbon::parse($this->selectedDate ?: now()->toDateString())->addDay()->toDateString() }}')"
                            class="flex-1 px-2.5 py-2 text-xs att-btn-secondary">
                        Besok &rarr;
                    </button>
                </div>
            </div>

            <!-- Date Banner Indicator -->
            <div class="mt-4 flex items-center justify-between text-xs px-3.5 py-2.5 rounded-xl {{ $audit['is_sunday'] ? 'att-badge-amber' : 'bg-gray-50 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-calendar-days class="w-4 h-4" />
                    <span class="font-bold">{{ $audit['day_name'] }}, {{ $audit['date_formatted'] }}</span>
                    @if($audit['is_sunday'])
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 text-amber-900 dark:bg-amber-900 dark:text-amber-100">Hari Minggu (Libur)</span>
                    @endif
                </div>
                <div>
                    Total Data: <span class="font-extrabold">{{ $summary['total_staff'] }} Karyawan</span>
                </div>
            </div>
        </div>

        {{-- 2. Summary Stat Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <!-- Total Staff -->
            <div class="att-stat-card">
                <div>
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Staff</div>
                    <div class="text-xl font-extrabold text-gray-900 dark:text-white mt-1">{{ $summary['total_staff'] }}</div>
                </div>
                <span class="p-2 rounded-xl att-badge-gray">
                    <x-heroicon-o-users class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </span>
            </div>

            <!-- Total Checked In -->
            <div class="att-stat-card">
                <div>
                    <div class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Total Hadir</div>
                    <div class="text-xl font-extrabold text-emerald-700 dark:text-emerald-300 mt-1">{{ $summary['total_checked_in'] }}</div>
                </div>
                <span class="p-2 rounded-xl att-badge-emerald">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </span>
            </div>

            <!-- Sudah Pulang -->
            <div class="att-stat-card">
                <div>
                    <div class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Selesai/Pulang</div>
                    <div class="text-xl font-extrabold text-blue-700 dark:text-blue-300 mt-1">{{ $summary['total_checked_out'] }}</div>
                </div>
                <span class="p-2 rounded-xl att-badge-blue">
                    <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </span>
            </div>

            <!-- Sedang Pause / Izin Keluar -->
            <div class="att-stat-card">
                <div>
                    <div class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Sedang Pause</div>
                    <div class="text-xl font-extrabold text-amber-700 dark:text-amber-300 mt-1">{{ $summary['total_currently_paused'] }}</div>
                </div>
                <span class="p-2 rounded-xl att-badge-amber">
                    <x-heroicon-o-pause-circle class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </span>
            </div>

            <!-- Izin / Cuti -->
            <div class="att-stat-card">
                <div>
                    <div class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Izin / Cuti</div>
                    <div class="text-xl font-extrabold text-purple-700 dark:text-purple-300 mt-1">{{ $summary['total_leaves'] }}</div>
                </div>
                <span class="p-2 rounded-xl att-badge-purple">
                    <x-heroicon-o-document-text class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </span>
            </div>

            <!-- Alpha -->
            <div class="att-stat-card">
                <div>
                    <div class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Alpha</div>
                    <div class="text-xl font-extrabold text-rose-700 dark:text-rose-300 mt-1">{{ $summary['total_alpha'] }}</div>
                </div>
                <span class="p-2 rounded-xl att-badge-rose">
                    <x-heroicon-o-x-circle class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                </span>
            </div>

            <!-- Jam Kerja Total -->
            <div class="att-stat-card">
                <div>
                    <div class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Total Jam Kerja</div>
                    <div class="text-xl font-extrabold text-indigo-700 dark:text-indigo-300 mt-1">{{ $summary['total_working_hours'] }}h</div>
                </div>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    <x-heroicon-o-clock class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </span>
            </div>
        </div>

        {{-- 3. Audit Table Card --}}
        <div class="att-card !p-0 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white">
                        Daftar Log Presensi & Audit Kepatuhan
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Klik foto thumbnail atau tombol jeda untuk melihat pratinjau audit secara lengkap.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th>Staff & Kantor</th>
                            <th>Status Hari Ini</th>
                            <th>Masuk & GPS</th>
                            <th>Pulang & GPS</th>
                            <th>Jeda Istirahat</th>
                            <th>Durasi Kerja</th>
                            <th>Lembur</th>
                            <th>Uang Hadir</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audit['staff_logs'] as $index => $staff)
                            <tr>
                                <td class="text-center font-medium text-gray-500">{{ $index + 1 }}</td>
                                
                                <!-- Staff Info -->
                                <td>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $staff['name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $staff['email'] }}</div>
                                    <div class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 mt-0.5">
                                        {{ $staff['office_name'] }}
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td>
                                    @php
                                        $badgeClass = match($staff['badge_color']) {
                                            'emerald' => 'att-badge-emerald',
                                            'sky' => 'att-badge-blue',
                                            'amber' => 'att-badge-amber',
                                            'rose' => 'att-badge-rose',
                                            default => 'att-badge-gray',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $badgeClass }}">
                                        {{ $staff['status_label'] }}
                                    </span>
                                </td>

                                <!-- Check In & GPS -->
                                <td>
                                    @if($staff['check_in_at'])
                                        <div class="flex items-center gap-2">
                                            @if($staff['check_in_selfie'])
                                                <img src="{{ $staff['check_in_selfie'] }}"
                                                     alt="Selfie Masuk"
                                                     wire:click="openImagePreview('{{ $staff['check_in_selfie'] }}', 'Foto Selfie Masuk - {{ $staff['name'] }}')"
                                                     class="thumb-preview"
                                                     title="Klik untuk memperbesar" />
                                            @endif
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-white font-mono">
                                                    {{ $staff['check_in_at'] }}
                                                </div>
                                                @if($staff['check_in_distance'] !== null)
                                                    <div class="text-[11px] mt-0.5">
                                                        <span class="{{ $staff['check_in_within_radius'] ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-rose-600 dark:text-rose-400 font-bold' }}">
                                                            {{ $staff['check_in_distance'] }}m
                                                            ({{ $staff['check_in_within_radius'] ? 'Radius OK' : 'Luar Radius' }})
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Check Out & GPS -->
                                <td>
                                    @if($staff['check_out_at'])
                                        <div class="flex items-center gap-2">
                                            @if($staff['check_out_selfie'])
                                                <img src="{{ $staff['check_out_selfie'] }}"
                                                     alt="Selfie Pulang"
                                                     wire:click="openImagePreview('{{ $staff['check_out_selfie'] }}', 'Foto Selfie Pulang - {{ $staff['name'] }}')"
                                                     class="thumb-preview"
                                                     title="Klik untuk memperbesar" />
                                            @endif
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-white font-mono">
                                                    {{ $staff['check_out_at'] }}
                                                </div>
                                                @if($staff['check_out_distance'] !== null)
                                                    <div class="text-[11px] mt-0.5">
                                                        <span class="{{ $staff['check_out_within_radius'] ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-rose-600 dark:text-rose-400 font-bold' }}">
                                                            {{ $staff['check_out_distance'] }}m
                                                            ({{ $staff['check_out_within_radius'] ? 'Radius OK' : 'Luar Radius' }})
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Breaks / Izin Keluar -->
                                <td>
                                    @if(!empty($staff['breaks']))
                                        <div>
                                            <div class="font-bold text-amber-700 dark:text-amber-300">
                                                {{ $staff['total_break_minutes'] }} menit
                                            </div>
                                            <button type="button"
                                                    wire:click="openBreaksModal({{ $staff['user_id'] }})"
                                                    class="text-[11px] text-blue-600 hover:text-blue-700 dark:text-blue-400 underline font-semibold mt-0.5">
                                                Lihat {{ count($staff['breaks']) }} Sesi Jeda
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">0 menit</span>
                                    @endif
                                </td>

                                <!-- Durasi Kerja -->
                                <td>
                                    @if($staff['working_hours'] > 0)
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ $staff['working_hours'] }} jam
                                        </div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                            ({{ $staff['working_minutes'] }} mnt)
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Lembur -->
                                <td>
                                    @if($staff['overtime_hours'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800">
                                            +{{ $staff['overtime_hours'] }} jam
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Uang Hadir -->
                                <td>
                                    @if($staff['allowance_amount'] > 0)
                                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                            Rp {{ number_format($staff['allowance_amount'], 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Keterangan -->
                                <td class="text-xs text-gray-600 dark:text-gray-300 max-w-xs">
                                    @if($staff['leave_request'])
                                        <span class="text-amber-700 dark:text-amber-300 font-medium">
                                            Izin: {{ $staff['leave_request']['reason'] }}
                                        </span>
                                    @elseif($staff['notes'])
                                        <span>{{ $staff['notes'] }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-gray-500 dark:text-gray-400">
                                    Tidak ada data presensi staff untuk tanggal yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 4. Selfie Photo Preview Modal --}}
    @if($this->showPreviewModal)
        <div class="modal-backdrop" wire:click="closeImagePreview">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-5 overflow-hidden border border-gray-200 dark:border-gray-700"
                 wire:click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-white">
                        {{ $this->previewImageTitle ?: 'Foto Selfie Presensi' }}
                    </h4>
                    <button type="button"
                            wire:click="closeImagePreview"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="mt-4 flex justify-center bg-gray-950 rounded-xl p-2">
                    <img src="{{ $this->previewImageUrl }}"
                         alt="Preview Selfie"
                         class="max-h-96 max-w-full rounded-lg object-contain" />
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button"
                            wire:click="closeImagePreview"
                            class="px-4 py-2 text-xs font-semibold att-btn-secondary">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- 5. Breaks Detail Modal --}}
    @if($this->showBreaksModal && $this->selectedBreaksDetail)
        <div class="modal-backdrop" wire:click="closeBreaksModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-5 overflow-hidden border border-gray-200 dark:border-gray-700"
                 wire:click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h4 class="text-base font-extrabold text-gray-900 dark:text-white">
                            Riwayat Jeda Istirahat / Izin Keluar
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $this->selectedBreaksDetail['name'] }} ({{ $audit['date_formatted'] }})
                        </p>
                    </div>
                    <button type="button"
                            wire:click="closeBreaksModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-3 max-h-80 overflow-y-auto pr-1">
                    @foreach($this->selectedBreaksDetail['breaks'] as $idx => $brk)
                        <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-gray-900 dark:text-white">
                                    Jeda #{{ $idx + 1 }}: {{ ucfirst(str_replace('_', ' ', $brk['reason'] ?: 'Istirahat')) }}
                                </span>
                                @if($brk['is_active'])
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold att-badge-amber">
                                        Sedang Berlangsung
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold att-badge-emerald">
                                        {{ $brk['duration_minutes'] }} menit
                                    </span>
                                @endif
                            </div>
                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-300 flex items-center gap-4">
                                <div>Mulai: <span class="font-mono font-bold">{{ $brk['paused_at'] ?: '-' }}</span></div>
                                <div>Kembali: <span class="font-mono font-bold">{{ $brk['resumed_at'] ?: 'Belum' }}</span></div>
                            </div>
                            @if($brk['notes'])
                                <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 italic">
                                    Catatan: {{ $brk['notes'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-xs font-bold text-gray-700 dark:text-gray-300">
                        Total Jeda: {{ $this->selectedBreaksDetail['total_break_minutes'] }} menit
                    </div>
                    <button type="button"
                            wire:click="closeBreaksModal"
                            class="px-4 py-2 text-xs font-semibold att-btn-secondary">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
