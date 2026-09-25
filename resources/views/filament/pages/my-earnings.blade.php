<x-filament-panels::page>
    @php
        $data = $this->earningsData;
        $currency = $data['currency'] ?? 'Rp';
    @endphp

    <style>
        .earn-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
            transition: all 0.2s ease;
        }

        .dark .earn-card {
            background: #1f2937;
            border-color: #374151;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }

        .earn-card-hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #ffffff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);
        }

        .dark .earn-card-hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid #334155;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }

        .earn-badge-blue {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        .dark .earn-badge-blue {
            background-color: rgba(30, 58, 138, 0.3);
            color: #93c5fd;
            border-color: #1e40af;
        }

        .earn-badge-emerald {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .dark .earn-badge-emerald {
            background-color: rgba(6, 78, 59, 0.3);
            color: #6ee7b7;
            border-color: #065f46;
        }

        .earn-badge-amber {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .dark .earn-badge-amber {
            background-color: rgba(120, 53, 15, 0.3);
            color: #fcd34d;
            border-color: #92400e;
        }

        .earn-badge-rose {
            background-color: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        .dark .earn-badge-rose {
            background-color: rgba(136, 19, 55, 0.3);
            color: #fda4af;
            border-color: #9f1239;
        }
    </style>

    <div class="space-y-6">
        {{-- 1. Top Header & Period Filter Bar --}}
        <div class="earn-card">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                {{-- Staff & Office Info --}}
                <div class="flex items-center gap-3">
                    <span class="p-3 rounded-2xl earn-badge-blue shrink-0">
                        <x-heroicon-o-banknotes class="w-6 h-6" />
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold text-gray-900 dark:text-white">
                                {{ $this->user?->name }}
                            </h2>
                            <span class="text-[11px] px-2 py-0.5 rounded-full earn-badge-blue font-bold">
                                {{ $this->office?->name ?? 'No Office' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Monitoring kalkulasi tunjangan kehadiran, lembur, dan rekap potongan denda Anda.
                        </p>
                    </div>
                </div>

                {{-- Month & Year Selector Form Controls --}}
                <div class="flex items-center gap-2 self-start md:self-auto bg-gray-50 dark:bg-gray-900/80 p-2 rounded-2xl border border-gray-200 dark:border-gray-700/80">
                    <div class="flex items-center gap-1.5">
                        <x-heroicon-o-calendar class="w-4 h-4 text-gray-500 dark:text-gray-400 ml-1" />
                        <select
                            wire:model.live="selectedMonth"
                            class="text-xs font-bold rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 pl-2.5 pr-7 focus:ring-blue-500 focus:border-blue-500 shadow-sm cursor-pointer">
                            @foreach ($this->months as $mNum => $mName)
                                <option value="{{ $mNum }}">{{ $mName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <select
                        wire:model.live="selectedYear"
                        class="text-xs font-bold rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white py-1.5 pl-2.5 pr-7 focus:ring-blue-500 focus:border-blue-500 shadow-sm cursor-pointer">
                        @foreach ($this->years as $yVal => $yLabel)
                            <option value="{{ $yVal }}">{{ $yLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. Four Financial Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            {{-- Card 1: Tunjangan Reguler --}}
            <div class="earn-card flex flex-col justify-between border-l-4 border-l-emerald-500">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tunjangan Hadir
                        </span>
                        <span class="p-2 rounded-xl earn-badge-emerald">
                            <x-heroicon-o-clock class="w-4 h-4" />
                        </span>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        {{ $currency }} {{ number_format($data['regular_allowance_total'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                    <span>Memenuhi Syarat: <strong>{{ $data['regular_allowance_eligible_days'] ?? 0 }} hari</strong></span>
                    <span>Hadir: <strong>{{ $data['present_days'] ?? 0 }} hari</strong></span>
                </div>
            </div>

            {{-- Card 2: Uang Lembur --}}
            <div class="earn-card flex flex-col justify-between border-l-4 border-l-amber-500">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Uang Lembur (OT)
                        </span>
                        <span class="p-2 rounded-xl earn-badge-amber">
                            <x-heroicon-o-fire class="w-4 h-4" />
                        </span>
                    </div>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400">
                        {{ $currency }} {{ number_format($data['overtime_allowance_total'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                    <span>Total Sesi: <strong>{{ $data['overtime_eligible_count'] ?? 0 }} sesi</strong></span>
                    <span>Durasi: <strong>{{ $data['total_overtime_duration'] ?? '0m' }}</strong></span>
                </div>
            </div>

            {{-- Card 3: Total Denda / Potongan --}}
            <div class="earn-card flex flex-col justify-between border-l-4 border-l-rose-500">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Total Denda (Potong Gaji)
                        </span>
                        <span class="p-2 rounded-xl earn-badge-rose">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        </span>
                    </div>
                    <div class="text-2xl font-black text-rose-600 dark:text-rose-400">
                        - {{ $currency }} {{ number_format($data['total_fines'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                    <span>Alpha: <strong>{{ $data['total_alpha_days'] ?? 0 }} hari</strong></span>
                    <span>Izin: <strong>{{ $currency }} {{ number_format($data['total_leave_fine'] ?? 0, 0, ',', '.') }}</strong></span>
                </div>
            </div>

            {{-- Card 4: Net Earnings (Total Bersih) --}}
            <div class="earn-card flex flex-col justify-between border-l-4 {{ ($data['net_earnings'] ?? 0) >= 0 ? 'border-l-blue-600' : 'border-l-rose-600' }} bg-gradient-to-br from-blue-50/50 to-indigo-50/30 dark:from-blue-950/20 dark:to-indigo-950/20">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-blue-900 dark:text-blue-200 uppercase tracking-wider">
                            Pendapatan Bersih (Net)
                        </span>
                        <span class="p-2 rounded-xl earn-badge-blue">
                            <x-heroicon-o-currency-dollar class="w-4 h-4" />
                        </span>
                    </div>
                    <div class="text-2xl font-black {{ ($data['net_earnings'] ?? 0) >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $currency }} {{ number_format($data['net_earnings'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
                <div class="pt-3 mt-3 border-t border-blue-200/50 dark:border-blue-800/40 text-[10px] text-blue-800 dark:text-blue-300 font-semibold flex items-center justify-between">
                    <span>Tunjangan Hadir + Lembur</span>
                    <span>{{ $data['period_label'] ?? '' }}</span>
                </div>
            </div>

        </div>

        {{-- 3. Interactive Detail Tabs --}}
        <div class="earn-card p-0 overflow-hidden" x-data="{ tab: @entangle('activeTab') }">
            {{-- Tab Header Buttons --}}
            <div class="flex items-center gap-1 border-b border-gray-100 dark:border-gray-700/80 bg-gray-50/70 dark:bg-gray-900/60 p-2 overflow-x-auto">
                <button
                    type="button"
                    @click="tab = 'summary'"
                    :class="tab === 'summary' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer shrink-0">
                    <x-heroicon-o-chart-bar class="w-4 h-4" />
                    <span>Ringkasan & Log Harian</span>
                </button>

                <button
                    type="button"
                    @click="tab = 'attendances'"
                    :class="tab === 'attendances' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer shrink-0">
                    <x-heroicon-o-clock class="w-4 h-4" />
                    <span>Rincian Absensi Reguler ({{ $data['attendances']->count() }})</span>
                </button>

                <button
                    type="button"
                    @click="tab = 'overtimes'"
                    :class="tab === 'overtimes' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer shrink-0">
                    <x-heroicon-o-fire class="w-4 h-4" />
                    <span>Rincian Lembur / OT ({{ $data['overtimes']->count() }})</span>
                </button>

                <button
                    type="button"
                    @click="tab = 'leaves_fines'"
                    :class="tab === 'leaves_fines' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer shrink-0">
                    <x-heroicon-o-document-text class="w-4 h-4" />
                    <span>Izin & Denda ({{ $data['leave_requests']->count() }})</span>
                </button>
            </div>

            {{-- Tab Content 1: Summary & Daily Log --}}
            <div x-show="tab === 'summary'" class="p-5 space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {{-- Financial Calculation Formula Card --}}
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800 space-y-3 lg:col-span-1">
                        <h4 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                            <x-heroicon-o-calculator class="w-4 h-4 text-blue-500" />
                            Rincian Pendapatan Bersih
                        </h4>

                        <div class="space-y-2 text-xs divide-y divide-gray-200/60 dark:divide-gray-700/60">
                            <div class="flex justify-between items-center pt-1">
                                <span class="text-gray-500 dark:text-gray-400">Tunjangan Kehadiran Reguler</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">+ {{ $currency }} {{ number_format($data['regular_allowance_total'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="text-gray-500 dark:text-gray-400">Tunjangan Lembur (OT)</span>
                                <span class="font-bold text-amber-600 dark:text-amber-400">+ {{ $currency }} {{ number_format($data['overtime_allowance_total'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-3 font-extrabold text-sm border-t-2 border-gray-300 dark:border-gray-600">
                                <span class="text-gray-900 dark:text-white">Total Pendapatan Bersih (Net)</span>
                                <span class="text-blue-600 dark:text-blue-400">{{ $currency }} {{ number_format($data['net_earnings'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700 space-y-2 text-xs">
                            <div class="text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Catatan Denda (Dipotong dari Gaji)
                            </div>
                            <div class="flex justify-between items-center text-rose-600 dark:text-rose-400">
                                <span>Denda Alpha ({{ $data['total_alpha_days'] }} hari)</span>
                                <span class="font-bold">- {{ $currency }} {{ number_format($data['total_alpha_fine'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-rose-600 dark:text-rose-400">
                                <span>Denda Izin / Cuti</span>
                                <span class="font-bold">- {{ $currency }} {{ number_format($data['total_leave_fine'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-1 font-bold text-rose-700 dark:text-rose-400 border-t border-gray-200 dark:border-gray-700">
                                <span>Total Denda Periode Ini</span>
                                <span>- {{ $currency }} {{ number_format($data['total_fines'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Working Days & Calendar Status Breakdown List --}}
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800 space-y-3 lg:col-span-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                <x-heroicon-o-calendar-days class="w-4 h-4 text-blue-500" />
                                Kalender Status Harian ({{ $data['period_label'] }})
                            </h4>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full earn-badge-blue">
                                {{ $data['working_days'] }} Hari Kerja Efektif
                            </span>
                        </div>

                        <div class="overflow-x-auto max-h-80 overflow-y-auto pr-1">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase font-semibold sticky top-0">
                                    <tr>
                                        <th class="py-2 px-3 rounded-l-lg">Tanggal</th>
                                        <th class="py-2 px-3">Hari</th>
                                        <th class="py-2 px-3">Status Kehadiran</th>
                                        <th class="py-2 px-3">Keterangan</th>
                                        <th class="py-2 px-3 text-right rounded-r-lg">Denda/Potongan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 text-gray-600 dark:text-gray-300">
                                    @forelse ($data['fines_breakdown'] as $dateStr => $dayInfo)
                                        @php
                                            $cDate = \Carbon\Carbon::parse($dateStr);
                                        @endphp
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                            <td class="py-2 px-3 font-mono font-bold text-gray-900 dark:text-white">
                                                {{ $cDate->format('d M Y') }}
                                            </td>
                                            <td class="py-2 px-3 text-gray-500 dark:text-gray-400">
                                                {{ $cDate->locale('id')->isoFormat('dddd') }}
                                            </td>
                                            <td class="py-2 px-3">
                                                @if ($dayInfo['status'] === 'present')
                                                    <span class="px-2 py-0.5 rounded-full earn-badge-emerald font-bold text-[10px]">Hadir</span>
                                                @elseif ($dayInfo['status'] === 'approved_leave')
                                                    <span class="px-2 py-0.5 rounded-full earn-badge-amber font-bold text-[10px]">Izin / Cuti</span>
                                                @elseif ($dayInfo['status'] === 'holiday')
                                                    <span class="px-2 py-0.5 rounded-full earn-badge-blue font-bold text-[10px]">Libur Nasional</span>
                                                @elseif ($dayInfo['status'] === 'alpha')
                                                    <span class="px-2 py-0.5 rounded-full earn-badge-rose font-bold text-[10px]">Alpha / Tidak Hadir</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-xs">
                                                {{ $dayInfo['description'] ?? '-' }}
                                            </td>
                                            <td class="py-2 px-3 text-right font-bold">
                                                @if (($dayInfo['fine_amount'] ?? 0) > 0)
                                                    <span class="text-rose-600 dark:text-rose-400 font-mono">- {{ $currency }} {{ number_format($dayInfo['fine_amount'], 0, ',', '.') }}</span>
                                                @else
                                                    <span class="text-gray-400">Rp 0</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-4 text-center text-gray-400">Belum ada data kalender untuk periode ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab Content 2: Attendances Table --}}
            <div x-show="tab === 'attendances'" class="p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 uppercase font-semibold text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="py-2.5 px-3 rounded-l-lg">Tanggal</th>
                                <th class="py-2.5 px-3">Jam Masuk</th>
                                <th class="py-2.5 px-3">Jam Pulang</th>
                                <th class="py-2.5 px-3">Durasi Kerja</th>
                                <th class="py-2.5 px-3">Izin Keluar</th>
                                <th class="py-2.5 px-3">Status Tunjangan</th>
                                <th class="py-2.5 px-3 text-right rounded-r-lg">Nominal Tunjangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse ($data['attendances'] as $att)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                    <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-white font-mono">
                                        {{ $att->attendance_date?->format('d M Y') }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono">
                                        {{ $att->check_in_at ? $att->check_in_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : '—' }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono">
                                        {{ $att->check_out_at ? $att->check_out_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : 'Sedang Berjalan' }}
                                    </td>
                                    <td class="py-2.5 px-3 font-semibold">
                                        {{ $att->working_minutes ? app(\App\Services\AttendanceCalculationService::class)->formatMinutesToDuration($att->working_minutes) : '—' }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($att->totalBreakMinutes() > 0)
                                            <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ app(\App\Services\AttendanceCalculationService::class)->formatMinutesToDuration($att->totalBreakMinutes()) }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($att->allowance_eligible)
                                            <span class="px-2.5 py-0.5 rounded-full earn-badge-emerald font-bold text-[10px]">Memenuhi Syarat</span>
                                        @elseif ($att->isCheckedOut())
                                            <span class="px-2.5 py-0.5 rounded-full earn-badge-rose font-bold text-[10px]">Tidak Memenuhi</span>
                                        @else
                                            <span class="text-gray-400">Sedang Berjalan</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold">
                                        @if ($att->allowance_eligible)
                                            <span class="text-emerald-600 dark:text-emerald-400 font-mono">+ {{ $currency }} {{ number_format($att->allowance_amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">Rp 0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-400 text-xs">Belum ada riwayat absensi pada bulan ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab Content 3: Overtime Sessions Table --}}
            <div x-show="tab === 'overtimes'" class="p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 uppercase font-semibold text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="py-2.5 px-3 rounded-l-lg">Tanggal Lembur</th>
                                <th class="py-2.5 px-3">Mulai Lembur</th>
                                <th class="py-2.5 px-3">Selesai Lembur</th>
                                <th class="py-2.5 px-3">Durasi Lembur</th>
                                <th class="py-2.5 px-3">Status Uang Lembur</th>
                                <th class="py-2.5 px-3 text-right rounded-r-lg">Nominal Uang Lembur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse ($data['overtimes'] as $ot)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                    <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-white font-mono">
                                        {{ $ot->overtime_date?->format('d M Y') }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono">
                                        {{ $ot->check_in_at ? $ot->check_in_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : '—' }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono">
                                        {{ $ot->check_out_at ? $ot->check_out_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : 'Sedang Berjalan' }}
                                    </td>
                                    <td class="py-2.5 px-3 font-semibold text-amber-600 dark:text-amber-400">
                                        {{ $ot->overtime_minutes ? app(\App\Services\AttendanceCalculationService::class)->formatMinutesToDuration($ot->overtime_minutes) : '—' }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($ot->allowance_eligible)
                                            <span class="px-2.5 py-0.5 rounded-full earn-badge-amber font-bold text-[10px]">Memenuhi Syarat</span>
                                        @elseif ($ot->isCheckedOut())
                                            <span class="px-2.5 py-0.5 rounded-full earn-badge-rose font-bold text-[10px]">Tidak Memenuhi</span>
                                        @else
                                            <span class="text-gray-400">Sedang Berjalan</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold">
                                        @if ($ot->allowance_eligible)
                                            <span class="text-amber-600 dark:text-amber-400 font-mono">+ {{ $currency }} {{ number_format($ot->allowance_amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">Rp 0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-400 text-xs">Belum ada sesi lembur pada bulan ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab Content 4: Leaves & Fines Table --}}
            <div x-show="tab === 'leaves_fines'" class="p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 uppercase font-semibold text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="py-2.5 px-3 rounded-l-lg">Periode Izin</th>
                                <th class="py-2.5 px-3">Jenis Izin</th>
                                <th class="py-2.5 px-3">Alasan</th>
                                <th class="py-2.5 px-3">Lampiran</th>
                                <th class="py-2.5 px-3">Status Persetujuan</th>
                                <th class="py-2.5 px-3">Denda Normal</th>
                                <th class="py-2.5 px-3 text-right rounded-r-lg">Denda Disetujui (Keringanan)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                            @forelse ($data['leave_requests'] as $leave)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                    <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-white font-mono">
                                        {{ $leave->start_date->format('d M Y') }}
                                        @if ($leave->start_date->ne($leave->end_date))
                                            - {{ $leave->end_date->format('d M Y') }}
                                        @endif
                                        <div class="text-[10px] text-gray-400 font-normal">({{ $leave->total_days }} hari kerja)</div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($leave->leave_type === 'sick')
                                            <span class="px-2 py-0.5 rounded-full earn-badge-rose font-bold text-[10px]">Sakit</span>
                                        @elseif ($leave->leave_type === 'permission')
                                            <span class="px-2 py-0.5 rounded-full earn-badge-amber font-bold text-[10px]">Izin</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full earn-badge-blue font-bold text-[10px]">Cuti</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 max-w-xs truncate" title="{{ $leave->reason }}">
                                        {{ $leave->reason }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($leave->attachment_path)
                                            @php
                                                $attUrl = rescue(
                                                    fn () => \Illuminate\Support\Facades\Storage::disk('gcs')->temporaryUrl($leave->attachment_path, now()->addHours(24)),
                                                    fn () => \Illuminate\Support\Facades\Storage::disk('gcs')->url($leave->attachment_path)
                                                );
                                            @endphp
                                            <a href="{{ $attUrl }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:underline font-bold text-[11px]">
                                                <x-heroicon-o-paper-clip class="w-3.5 h-3.5" />
                                                <span>Lihat</span>
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if ($leave->status === 'approved')
                                            <span class="px-2 py-0.5 rounded-full earn-badge-emerald font-bold text-[10px]">Disetujui</span>
                                        @elseif ($leave->status === 'rejected')
                                            <span class="px-2 py-0.5 rounded-full earn-badge-rose font-bold text-[10px]">Ditolak</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full earn-badge-amber font-bold text-[10px]">Menunggu Approval</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-gray-500">
                                        {{ $currency }} {{ number_format($leave->normal_fine_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold font-mono">
                                        @if ($leave->status === 'approved')
                                            <span class="{{ $leave->adjusted_fine_amount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                {{ $currency }} {{ number_format($leave->adjusted_fine_amount, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">Menunggu</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-400 text-xs">Tidak ada permohonan izin pada bulan ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
