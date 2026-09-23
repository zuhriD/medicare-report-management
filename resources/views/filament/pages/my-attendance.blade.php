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

        .att-btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-weight: 700;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .att-btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .att-btn-success {
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            font-weight: 700;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .att-btn-success:hover:not(:disabled) {
            background: linear-gradient(135deg, #047857, #065f46);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }

        .att-btn-danger {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #ffffff;
            font-weight: 700;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .att-btn-danger:hover:not(:disabled) {
            background: linear-gradient(135deg, #be123c, #9f1239);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
        }

        .att-btn-warning {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #ffffff;
            font-weight: 700;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .att-btn-warning:hover:not(:disabled) {
            background: linear-gradient(135deg, #b45309, #92400e);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
        }
    </style>

    <div class="space-y-5" x-data="attendanceManager()">
        {{-- 1. Compact Header Bar: Office, Policy & Live Clock --}}
        <div class="att-card">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                {{-- Office & Timezone Info --}}
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-xl att-badge-blue shrink-0">
                        <x-heroicon-o-building-office-2 class="w-6 h-6" />
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold text-gray-900 dark:text-white">
                                {{ $this->office?->name ?? 'No Office Assigned' }}
                            </h2>
                            <span class="text-[11px] px-2 py-0.5 rounded-full att-badge-blue font-semibold">
                                {{ $this->office?->timezone ?? config('app.timezone') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $this->office?->address ?? 'Please contact HR to assign your work location.' }}
                        </p>
                    </div>
                </div>

                {{-- Policy Snapshot Inline Pills --}}
                @if ($this->policy)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg att-badge-blue">
                        Regular: {{ substr($this->policy->regular_check_in_start, 0, 5) }} - {{ substr($this->policy->regular_check_out_end, 0, 5) }} (Min {{ round($this->policy->minimum_regular_minutes / 60, 1) }}h)
                    </span>
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg att-badge-amber">
                        OT: {{ substr($this->policy->overtime_check_in_start, 0, 5) }} - {{ substr($this->policy->overtime_check_out_end, 0, 5) }} (Min {{ round($this->policy->minimum_overtime_minutes / 60, 1) }}h)
                    </span>
                </div>
                @endif

                {{-- Live Digital Clock --}}
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-900/80 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700/80 shrink-0 self-start lg:self-auto">
                    <x-heroicon-o-clock class="w-5 h-5 text-blue-600 dark:text-blue-400 animate-pulse" />
                    <div class="text-xl font-black font-mono tracking-wider text-blue-600 dark:text-blue-400" x-text="currentTime">
                        --:--:--
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Compact 3-Column Interactive Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

            {{-- COLUMN 1: Camera & Selfie Snapshot Hub --}}
            <div class="att-card flex flex-col justify-between space-y-3">
                <div>
                    {{-- Header with Status Badge --}}
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-sm font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-camera class="w-4 h-4 text-blue-500" />
                            Live Selfie Camera
                        </h3>
                        <span
                            class="text-[11px] px-2.5 py-0.5 rounded-full font-bold transition"
                            :class="hasSelfie ? 'att-badge-emerald' : (isCameraOpen ? 'att-badge-amber' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400')"
                            x-text="hasSelfie ? '✓ Foto Siap' : (isCameraOpen ? 'Kamera Aktif' : 'Wajib Foto')">
                        </span>
                    </div>

                    {{-- Camera Viewport / Preview Box --}}
                    <div class="relative w-full aspect-video sm:aspect-4/3 max-h-56 bg-gray-950 rounded-xl overflow-hidden flex items-center justify-center border border-gray-300 dark:border-gray-700 shadow-inner">
                        {{-- Live Video Stream --}}
                        <video
                            x-ref="videoElement"
                            autoplay
                            playsinline
                            class="w-full h-full object-cover transform -scale-x-100"
                            x-show="isCameraOpen && !hasSelfie"></video>

                        {{-- Captured Snapshot Preview --}}
                        <img
                            :src="selfiePreview"
                            x-show="hasSelfie"
                            class="w-full h-full object-cover"
                            alt="Captured Selfie" />

                        {{-- Idle Placeholder --}}
                        <div x-show="!isCameraOpen && !hasSelfie" class="text-center p-4">
                            <div class="w-12 h-12 mx-auto rounded-xl att-badge-blue flex items-center justify-center mb-2">
                                <x-heroicon-o-video-camera class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <p class="text-xs font-bold text-gray-200">Kamera Belum Aktif</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Klik tombol di bawah untuk buka kamera</p>
                        </div>

                        {{-- Hidden Canvas --}}
                        <canvas x-ref="canvasElement" class="hidden"></canvas>
                    </div>

                    {{-- Camera Action Buttons (ALWAYS PROMINENT & VISIBLE) --}}
                    <div class="mt-3">
                        <template x-if="!isCameraOpen && !hasSelfie">
                            <button
                                type="button"
                                @click="startCamera()"
                                class="w-full py-2.5 px-3 att-btn-primary text-xs flex items-center justify-center gap-2 cursor-pointer shadow">
                                <x-heroicon-m-video-camera class="w-4 h-4" />
                                <span>Buka Kamera Live</span>
                            </button>
                        </template>

                        <template x-if="isCameraOpen && !hasSelfie">
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    @click="captureSnapshot()"
                                    class="flex-1 py-2.5 px-3 att-btn-success text-xs font-bold flex items-center justify-center gap-2 cursor-pointer shadow-md ring-2 ring-emerald-400 animate-pulse">
                                    <x-heroicon-m-camera class="w-4 h-4" />
                                    <span>Ambil Snapshot Foto</span>
                                </button>
                                <button
                                    type="button"
                                    @click="stopCamera()"
                                    class="py-2.5 px-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition cursor-pointer">
                                    Batal
                                </button>
                            </div>
                        </template>

                        <template x-if="hasSelfie">
                            <button
                                type="button"
                                @click="retakeSnapshot()"
                                class="w-full py-2.5 px-3 att-btn-warning text-xs flex items-center justify-center gap-2 cursor-pointer shadow">
                                <x-heroicon-m-arrow-path class="w-4 h-4" />
                                <span>Ambil Ulang Foto Selfie</span>
                            </button>
                        </template>
                    </div>

                    {{-- GPS Indicator inside Hub --}}
                    <div class="mt-3 p-2.5 rounded-xl border text-xs" :class="isWithinRadius ? 'att-badge-emerald' : 'att-badge-rose'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 font-bold">
                                <span class="w-2 h-2 rounded-full" :class="isWithinRadius ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                                <span x-text="isWithinRadius ? 'GPS: Dalam Radius Kantor' : 'GPS: Luar Radius Kantor'"></span>
                            </div>
                            <button
                                type="button"
                                @click="detectLocation()"
                                class="text-[11px] font-bold underline cursor-pointer hover:opacity-80 flex items-center gap-1">
                                <span :class="{'animate-spin': isLocating}">↻</span> Refresh
                            </button>
                        </div>
                        <p class="text-[10px] mt-1 opacity-80" x-text="geofenceMessage"></p>
                    </div>
                </div>

                {{-- Notes / Remarks Input --}}
                <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60">
                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Catatan / Keterangan (Opsional)</label>
                    <input
                        type="text"
                        wire:model="notes"
                        placeholder="Contoh: Tugas harian, meeting client..."
                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900/50 focus:ring-blue-500 focus:border-blue-500 dark:text-white px-3 py-2" />
                </div>
            </div>

            {{-- COLUMN 2: Regular Attendance Card --}}
            <div class="att-card flex flex-col justify-between space-y-4">
                <div>
                    {{-- Header with Status Badge --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl att-badge-blue shrink-0">
                                <x-heroicon-o-clock class="w-5 h-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">Absensi Reguler</h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Jam kerja harian</p>
                            </div>
                        </div>

                        @if (!$this->todayAttendance)
                        <span class="px-2.5 py-1 att-badge-amber text-[11px] font-bold rounded-full">
                            Belum Check-In
                        </span>
                        @elseif ($this->todayAttendance->isPaused())
                        <span class="px-2.5 py-1 att-badge-amber text-[11px] font-bold rounded-full flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                            Izin Keluar (Paused)
                        </span>
                        @elseif (!$this->todayAttendance->isCheckedOut())
                        <span class="px-2.5 py-1 att-badge-blue text-[11px] font-bold rounded-full flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-ping"></span>
                            Sedang Bekerja
                        </span>
                        @else
                        <span class="px-2.5 py-1 att-badge-emerald text-[11px] font-bold rounded-full">
                            Selesai Hari Ini
                        </span>
                        @endif
                    </div>

                    {{-- Active Pause Notice if currently Paused --}}
                    @if ($this->todayAttendance?->isPaused())
                    @php
                    $activeBreak = $this->todayAttendance->activeBreak();
                    @endphp
                    <div class="p-3 bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xl text-xs space-y-1 mb-3">
                        <div class="flex items-center justify-between font-bold text-amber-800 dark:text-amber-300">
                            <span class="flex items-center gap-1.5">
                                <x-heroicon-s-pause-circle class="w-4 h-4 text-amber-600 animate-pulse" />
                                Izin Keluar Aktif
                            </span>
                            <span class="font-mono">
                                {{ $activeBreak?->paused_at ? $activeBreak->paused_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : '—' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-amber-900 dark:text-amber-200">
                            Alasan: <strong class="underline">{{ $activeBreak?->reason }}</strong>
                        </p>
                        @if ($activeBreak?->notes)
                        <p class="text-[10px] text-amber-700 dark:text-amber-400 italic">
                            "{{ $activeBreak->notes }}"
                        </p>
                        @endif
                    </div>
                    @endif

                    {{-- Regular Timeline Grid --}}
                    <div class="space-y-2.5 p-3.5 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-100 dark:border-gray-700/50 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Jam Masuk:</span>
                            <span class="font-black text-gray-900 dark:text-white">
                                {{ $this->todayAttendance?->check_in_at ? $this->todayAttendance->check_in_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : '—' }}
                            </span>
                        </div>

                        {{-- Total Break Info (if any) --}}
                        @if ($this->todayAttendance && ($this->todayAttendance->totalBreakMinutes() > 0 || $this->todayAttendance->isPaused()))
                        <div class="flex justify-between items-center text-amber-600 dark:text-amber-400">
                            <span class="font-medium">Total Izin Keluar/Jeda:</span>
                            <span class="font-bold">
                                {{ app(\App\Services\AttendanceCalculationService::class)->formatMinutesToDuration($this->todayAttendance->totalBreakMinutes()) }}
                                @if ($this->todayAttendance->isPaused())
                                <span class="text-[10px] font-normal italic">(berjalan...)</span>
                                @endif
                            </span>
                        </div>
                        @endif

                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Jam Pulang:</span>
                            <span class="font-black text-gray-900 dark:text-white">
                                {{ $this->todayAttendance?->check_out_at ? $this->todayAttendance->check_out_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : ($this->todayAttendance ? ($this->todayAttendance->isPaused() ? 'Dijeda (Izin)' : 'Sedang Berjalan') : '—') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-200/60 dark:border-gray-700/50">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Durasi Kerja Efektif:</span>
                            <span class="font-black text-blue-600 dark:text-blue-400">
                                @if ($this->todayAttendance?->isCheckedOut())
                                {{ app(\App\Services\AttendanceCalculationService::class)->formatMinutesToDuration($this->todayAttendance->working_minutes) }}
                                @elseif ($this->todayAttendance)
                                <span x-text="calculateElapsedDuration('{{ $this->todayAttendance->check_in_at->toISOString() }}')"></span>
                                @else
                                —
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Tunjangan:</span>
                            <span>
                                @if ($this->todayAttendance?->allowance_eligible)
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">Memenuhi Syarat</span>
                                @elseif ($this->todayAttendance?->isCheckedOut())
                                <span class="font-bold text-rose-500">Tidak Memenuhi</span>
                                @else
                                <span class="text-gray-400">Min. {{ round(($this->policy?->minimum_regular_minutes ?? 360) / 60, 1) }} jam</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Checklist Status --}}
                    <div class="mt-3 flex items-center justify-between text-[11px] px-1 text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1 font-semibold">
                            <span :class="isWithinRadius ? 'text-emerald-500 font-bold' : 'text-rose-500'">●</span>
                            <span x-text="isWithinRadius ? 'GPS OK' : 'GPS Belum Sesuai'"></span>
                        </span>
                        <span class="flex items-center gap-1 font-semibold">
                            <span :class="hasSelfie ? 'text-emerald-500 font-bold' : 'text-amber-500'">●</span>
                            <span x-text="hasSelfie ? 'Selfie OK' : 'Selfie Diperlukan'"></span>
                        </span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-2 space-y-2">
                    @if (!$this->todayAttendance)
                    <button
                        type="button"
                        wire:click="doRegularCheckIn"
                        wire:loading.attr="disabled"
                        :disabled="!isWithinRadius || !hasSelfie"
                        class="w-full py-3 px-4 att-btn-primary disabled:opacity-40 disabled:cursor-not-allowed text-xs font-bold flex items-center justify-center gap-2 cursor-pointer shadow-md">
                        <x-heroicon-m-arrow-right-on-rectangle class="w-4 h-4" />
                        <span wire:loading.remove wire:target="doRegularCheckIn">Check-In Reguler</span>
                        <span wire:loading wire:target="doRegularCheckIn">Memproses...</span>
                    </button>
                    @elseif ($this->todayAttendance->isPaused())
                    {{-- Button Resume (Kembali ke Kantor) --}}
                    <button
                        type="button"
                        wire:click="resumeAttendance"
                        wire:loading.attr="disabled"
                        :disabled="!isWithinRadius"
                        class="w-full py-3 px-4 att-btn-success disabled:opacity-40 disabled:cursor-not-allowed text-xs font-bold flex items-center justify-center gap-2 cursor-pointer shadow-md">
                        <!-- <x-heroicon-m-play class="w-4 h-4" /> -->
                        <span wire:loading.remove wire:target="resumeAttendance">Kembali ke Kantor (Lanjutkan Absensi)</span>
                        <span wire:loading wire:target="resumeAttendance">Memproses...</span>
                    </button>
                    {{-- Button Direct Check Out from Pause (Bisa langsung dari luar kantor dengan selfie) --}}
                    <button
                        type="button"
                        wire:click="doRegularCheckOut"
                        wire:loading.attr="disabled"
                        :disabled="!hasSelfie"
                        class="w-full py-2.5 px-3 att-btn-danger disabled:opacity-40 disabled:cursor-not-allowed text-xs font-bold flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                        <x-heroicon-m-arrow-left-on-rectangle class="w-4 h-4" />
                        <span wire:loading.remove wire:target="doRegularCheckOut">Selesaikan & Check-Out Langsung (Dari Luar)</span>
                        <span wire:loading wire:target="doRegularCheckOut">Memproses...</span>
                    </button>
                    @elseif (!$this->todayAttendance->isCheckedOut())
                    <div class="grid grid-cols-2 gap-2">
                        {{-- Button Izin Keluar (Pause) --}}
                        <button
                            type="button"
                            wire:click="openPauseModal"
                            class="w-full py-2.5 px-3 att-btn-warning text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer shadow">
                            <x-heroicon-m-pause class="w-4 h-4" />
                            <span>Izin Keluar</span>
                        </button>
                        {{-- Button Regular Check Out --}}
                        <button
                            type="button"
                            wire:click="doRegularCheckOut"
                            wire:loading.attr="disabled"
                            :disabled="!isWithinRadius || !hasSelfie"
                            class="w-full py-2.5 px-3 att-btn-danger disabled:opacity-40 disabled:cursor-not-allowed text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer shadow">
                            <x-heroicon-m-arrow-left-on-rectangle class="w-4 h-4" />
                            <span wire:loading.remove wire:target="doRegularCheckOut">Check-Out</span>
                            <span wire:loading wire:target="doRegularCheckOut">Proses...</span>
                        </button>
                    </div>
                    @else
                    <div class="p-3 att-badge-emerald rounded-xl text-xs font-bold flex items-center gap-2 justify-center">
                        <x-heroicon-m-check-badge class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>Absensi Reguler Selesai</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- COLUMN 3: Overtime (OT) Session Card --}}
            @php
            $isOtEligible = $this->todayAttendance?->isOvertimeEligible() ?? false;
            @endphp
            <div class="att-card flex flex-col justify-between space-y-4 {{ $isOtEligible ? 'border-amber-300 dark:border-amber-700/60' : 'opacity-85' }}">
                <div>
                    {{-- Header with Status Badge --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl {{ $isOtEligible ? 'att-badge-amber' : 'bg-gray-100 dark:bg-gray-800 text-gray-400' }} shrink-0">
                                <x-heroicon-o-fire class="w-5 h-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white flex items-center gap-1.5">
                                    Sesi Lembur (OT)
                                    @if ($isOtEligible)
                                    <span class="text-[9px] px-1.5 py-0.5 att-badge-amber rounded-full font-black">Eligible</span>
                                    @endif
                                </h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Overtime & uang lembur</p>
                            </div>
                        </div>

                        @if (!$isOtEligible)
                        <span class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-[11px] font-bold rounded-full flex items-center gap-1">
                            <x-heroicon-m-lock-closed class="w-3 h-3" />
                            Terkunci
                        </span>
                        @elseif (!$this->todayOvertime)
                        <span class="px-2.5 py-1 att-badge-amber text-[11px] font-bold rounded-full">
                            Siap Lembur
                        </span>
                        @elseif (!$this->todayOvertime->isCheckedOut())
                        <span class="px-2.5 py-1 att-badge-amber text-[11px] font-bold rounded-full flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                            Lembur Aktif
                        </span>
                        @else
                        <span class="px-2.5 py-1 att-badge-emerald text-[11px] font-bold rounded-full">
                            OT Selesai
                        </span>
                        @endif
                    </div>

                    @if (!$isOtEligible)
                    {{-- Locked Explanatory Box --}}
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-900/40 rounded-xl text-xs text-gray-600 dark:text-gray-400 space-y-1.5 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-1.5 font-bold text-gray-800 dark:text-gray-200">
                            <x-heroicon-o-information-circle class="w-4 h-4 text-amber-500 shrink-0" />
                            <span>Syarat Lembur Belum Terpenuhi</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            Fitur lembur aktif setelah Anda menyelesaikan Check-Out Reguler dengan minimal kerja <strong>{{ $this->policy?->minimum_regular_minutes ?? 360 }} menit ({{ round(($this->policy?->minimum_regular_minutes ?? 360)/60, 1) }} jam)</strong> hari ini.
                        </p>
                    </div>
                    @else
                    {{-- OT Timeline Grid --}}
                    <div class="space-y-2.5 p-3.5 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-200 dark:border-amber-900/30 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Mulai Lembur:</span>
                            <span class="font-black text-gray-900 dark:text-white">
                                {{ $this->todayOvertime?->check_in_at ? $this->todayOvertime->check_in_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : '—' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Selesai Lembur:</span>
                            <span class="font-black text-gray-900 dark:text-white">
                                {{ $this->todayOvertime?->check_out_at ? $this->todayOvertime->check_out_at->setTimezone($this->office?->timezone ?? config('app.timezone'))->format('H:i:s') : ($this->todayOvertime ? 'Sedang Berjalan' : '—') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-amber-200/60 dark:border-amber-900/40">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Durasi Lembur:</span>
                            <span class="font-black text-amber-700 dark:text-amber-400">
                                @if ($this->todayOvertime?->isCheckedOut())
                                {{ app(\App\Services\AttendanceCalculationService::class)->formatMinutesToDuration($this->todayOvertime->overtime_minutes) }}
                                @elseif ($this->todayOvertime)
                                <span x-text="calculateElapsedDuration('{{ $this->todayOvertime->check_in_at->toISOString() }}')"></span>
                                @else
                                —
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Uang Lembur:</span>
                            <span>
                                @if ($this->todayOvertime?->allowance_eligible)
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">Memenuhi Syarat</span>
                                @elseif ($this->todayOvertime?->isCheckedOut())
                                <span class="font-bold text-rose-500">Tidak Memenuhi</span>
                                @else
                                <span class="text-gray-400">Min. {{ round(($this->policy?->minimum_overtime_minutes ?? 60) / 60, 1) }} jam</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- OT Action Buttons --}}
                <div class="pt-2">
                    @if ($isOtEligible)
                    @if (!$this->todayOvertime)
                    <button
                        type="button"
                        wire:click="doOvertimeCheckIn"
                        wire:loading.attr="disabled"
                        :disabled="!isWithinRadius || !hasSelfie"
                        class="w-full py-3 px-4 att-btn-warning disabled:opacity-40 disabled:cursor-not-allowed text-xs font-bold flex items-center justify-center gap-2 cursor-pointer shadow-md">
                        <x-heroicon-m-fire class="w-4 h-4" />
                        <span wire:loading.remove wire:target="doOvertimeCheckIn">Mulai Lembur (OT Check-In)</span>
                        <span wire:loading wire:target="doOvertimeCheckIn">Memproses...</span>
                    </button>
                    @elseif (!$this->todayOvertime->isCheckedOut())
                    <button
                        type="button"
                        wire:click="doOvertimeCheckOut"
                        wire:loading.attr="disabled"
                        :disabled="!isWithinRadius || !hasSelfie"
                        class="w-full py-3 px-4 att-btn-danger disabled:opacity-40 disabled:cursor-not-allowed text-xs font-bold flex items-center justify-center gap-2 cursor-pointer shadow-md">
                        <x-heroicon-m-stop class="w-4 h-4" />
                        <span wire:loading.remove wire:target="doOvertimeCheckOut">Selesai Lembur (OT Check-Out)</span>
                        <span wire:loading wire:target="doOvertimeCheckOut">Memproses...</span>
                    </button>
                    @else
                    <div class="p-3 att-badge-emerald rounded-xl text-xs font-bold flex items-center gap-2 justify-center">
                        <x-heroicon-m-check-badge class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>Sesi Lembur Selesai</span>
                    </div>
                    @endif
                    @else
                    <button
                        type="button"
                        disabled
                        class="w-full py-3 px-4 bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 rounded-xl text-xs font-bold flex items-center justify-center gap-2 cursor-not-allowed">
                        <x-heroicon-m-lock-closed class="w-4 h-4" />
                        <span>Lembur Belum Tersedia</span>
                    </button>
                    @endif
                </div>
            </div>

        </div>

        {{-- 3. Bottom Section: Personal Recent Attendance History --}}
        <div class="att-card">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <x-heroicon-o-table-cells class="w-4 h-4 text-blue-500" />
                Riwayat Absensi Saya (15 Hari Terakhir)
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-900/60 uppercase font-semibold text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="py-2.5 px-3 rounded-l-lg">Tanggal</th>
                            <th class="py-2.5 px-3">Kantor</th>
                            <th class="py-2.5 px-3">Jam Masuk</th>
                            <th class="py-2.5 px-3">Jam Pulang</th>
                            <th class="py-2.5 px-3">Durasi Kerja</th>
                            <th class="py-2.5 px-3">Tunjangan Reg.</th>
                            <th class="py-2.5 px-3">Durasi Lembur</th>
                            <th class="py-2.5 px-3">Tunjangan OT</th>
                            <th class="py-2.5 px-3 rounded-r-lg">Selfie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse ($this->recentAttendances as $row)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                            <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-white">
                                {{ $row->attendance_date?->format('d M Y') }}
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded font-medium">
                                    {{ $row->office?->name }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                {{ $row->check_in_at ? $row->check_in_at->setTimezone($row->office?->timezone ?? config('app.timezone'))->format('H:i') : '—' }}
                            </td>
                            <td class="py-2.5 px-3">
                                {{ $row->check_out_at ? $row->check_out_at->setTimezone($row->office?->timezone ?? config('app.timezone'))->format('H:i') : 'In Progress' }}
                            </td>
                            <td class="py-2.5 px-3 font-semibold">
                                {{ $row->working_minutes ? app(\App\Services\AttendanceCalculationService::class)->formatMinutesToDuration($row->working_minutes) : '—' }}
                            </td>
                            <td class="py-2.5 px-3">
                                @if ($row->allowance_eligible)
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 rounded-full font-bold">
                                    {{ number_format($row->allowance_amount, 2) }}
                                </span>
                                @else
                                <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 font-semibold">
                                {{ $row->overtime?->overtime_minutes ? app(\App\Services\AttendanceCalculationService::class)->formatMinutesToDuration($row->overtime->overtime_minutes) : '—' }}
                            </td>
                            <td class="py-2.5 px-3">
                                @if ($row->overtime?->allowance_eligible)
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 rounded-full font-bold">
                                    {{ number_format($row->overtime->allowance_amount, 2) }}
                                </span>
                                @else
                                <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3">
                                @if ($row->check_in_selfie_url)
                                <a href="{{ $row->check_in_selfie_url }}" target="_blank">
                                    <img src="{{ $row->check_in_selfie_url }}" class="w-7 h-7 rounded-full object-cover border border-gray-200 dark:border-gray-700 shadow-sm hover:scale-110 transition" alt="Selfie" />
                                </a>
                                @else
                                <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="py-4 text-center text-gray-400 text-xs">
                                Belum ada data absensi.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. Modal Dialog: Izin Keluar (Pause Attendance) --}}
        @if ($showPauseModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-5 space-y-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2.5 rounded-xl att-badge-amber">
                            <x-heroicon-o-pause class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 dark:text-white">
                                Izin Keluar Kantor
                            </h3>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Jeda sementara absensi reguler</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closePauseModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-800/40 rounded-xl text-xs text-amber-800 dark:text-amber-300 space-y-1">
                    <p class="font-bold flex items-center gap-1">
                        <x-heroicon-s-information-circle class="w-4 h-4 text-amber-600 shrink-0" />
                        Informasi Jeda Waktu Kerja:
                    </p>
                    <p class="text-[11px] leading-relaxed text-amber-900 dark:text-amber-200">
                        Waktu selama izin keluar akan dijeda dari total durasi kerja hari ini. Anda dapat mengaktifkan kembali absensi dengan menekan tombol <strong>"Kembali ke Kantor"</strong> ketika tiba di lokasi kantor.
                    </p>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Alasan Izin Keluar <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="pauseReason"
                            placeholder="Contoh: Istirahat Makan Siang / Keperluan Medis / Dinas Luar..."
                            class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-amber-500 focus:border-amber-500 dark:text-white p-2.5 shadow-sm" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Catatan / Keterangan Tambahan (Opsional)
                        </label>
                        <textarea
                            wire:model="pauseNotes"
                            rows="2"
                            placeholder="Tuliskan catatan tambahan jika ada..."
                            class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-amber-500 focus:border-amber-500 dark:text-white p-2.5 shadow-sm"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="closePauseModal"
                        class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="pauseAttendance"
                        wire:loading.attr="disabled"
                        class="px-4 py-2.5 text-xs font-bold text-white att-btn-warning rounded-xl shadow-md flex items-center gap-1.5 cursor-pointer">
                        <x-heroicon-m-pause class="w-4 h-4" />
                        <span wire:loading.remove wire:target="pauseAttendance">Konfirmasi Izin Keluar</span>
                        <span wire:loading wire:target="pauseAttendance">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Alpine.js Attendance & WebRTC Camera Controller --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('attendanceManager', () => ({
                currentTime: '',
                timezone: '{{ $this->office?->timezone ?? config('app.timezone') }}',

                // Geolocation State
                latitude: @entangle('latitude'),
                longitude: @entangle('longitude'),
                accuracy: @entangle('accuracy'),
                isWithinRadius: @entangle('isWithinRadius'),
                geofenceMessage: @entangle('geofenceMessage'),
                isLocating: false,

                // Camera State
                isCameraOpen: false,
                hasSelfie: false,
                selfiePreview: null,
                stream: null,

                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);
                    this.detectLocation();
                },

                updateClock() {
                    try {
                        const now = new Date();
                        const formatter = new Intl.DateTimeFormat('en-GB', {
                            timeZone: this.timezone,
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: false
                        });
                        this.currentTime = formatter.format(now);
                    } catch (e) {
                        const now = new Date();
                        this.currentTime = now.toTimeString().split(' ')[0];
                    }
                },

                calculateElapsedDuration(startTimeIso) {
                    if (!startTimeIso) return '';
                    const start = new Date(startTimeIso).getTime();
                    const now = new Date().getTime();
                    const diffMinutes = Math.max(0, Math.floor((now - start) / 60000));
                    const hours = Math.floor(diffMinutes / 60);
                    const minutes = diffMinutes % 60;
                    return hours > 0 ? `${hours}j ${minutes}m berjalan` : `${minutes}m berjalan`;
                },

                detectLocation() {
                    if (!navigator.geolocation) {
                        alert('Geolocation tidak didukung pada browser ini.');
                        return;
                    }

                    if (!window.isSecureContext && location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                        console.warn('Peringatan: Browser membatasi akses Geolocation pada protokol HTTP non-secure. Pastikan domain menggunakan HTTPS.');
                    }

                    this.isLocating = true;

                    const onSuccess = (position) => {
                        this.latitude = position.coords.latitude;
                        this.longitude = position.coords.longitude;
                        this.accuracy = position.coords.accuracy;
                        this.isLocating = false;

                        // Sync with Livewire backend
                        this.$wire.updateCoordinates(this.latitude, this.longitude, this.accuracy);
                    };

                    const tryLowAccuracy = (primaryError) => {
                        console.warn('High accuracy GPS timed out or unavailable, falling back to network triangulation...', primaryError);
                        
                        navigator.geolocation.getCurrentPosition(
                            onSuccess,
                            (fallbackError) => {
                                // Try watchPosition as a final resort
                                let watchId = null;
                                const timeoutId = setTimeout(() => {
                                    if (watchId !== null) {
                                        navigator.geolocation.clearWatch(watchId);
                                    }
                                    this.isLocating = false;
                                    this.handleGeoError(fallbackError || primaryError);
                                }, 8000);

                                try {
                                    watchId = navigator.geolocation.watchPosition(
                                        (watchPos) => {
                                            clearTimeout(timeoutId);
                                            if (watchId !== null) {
                                                navigator.geolocation.clearWatch(watchId);
                                            }
                                            onSuccess(watchPos);
                                        },
                                        (watchErr) => {
                                            clearTimeout(timeoutId);
                                            if (watchId !== null) {
                                                navigator.geolocation.clearWatch(watchId);
                                            }
                                            this.isLocating = false;
                                            this.handleGeoError(watchErr);
                                        },
                                        {
                                            enableHighAccuracy: false,
                                            timeout: 7000,
                                            maximumAge: 60000
                                        }
                                    );
                                } catch (e) {
                                    clearTimeout(timeoutId);
                                    this.isLocating = false;
                                    this.handleGeoError(fallbackError || primaryError);
                                }
                            },
                            {
                                enableHighAccuracy: false,
                                timeout: 12000,
                                maximumAge: 60000
                            }
                        );
                    };

                    // Step 1: Try high accuracy first with cached allowance
                    navigator.geolocation.getCurrentPosition(
                        onSuccess,
                        (error) => {
                            if (error.code === 3 || error.code === 2) {
                                // Timeout or unavailable -> immediately fallback to network positioning
                                tryLowAccuracy(error);
                            } else {
                                this.isLocating = false;
                                this.handleGeoError(error);
                            }
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 8000,
                            maximumAge: 30000
                        }
                    );
                },

                handleGeoError(error) {
                    console.error('Geolocation error:', error);
                    let msg = 'Gagal mendeteksi lokasi GPS.';
                    if (error.code === 1) { // PERMISSION_DENIED
                        msg = 'Izin lokasi (GPS) ditolak/diblokir oleh browser. Harap klik ikon gembok/pengaturan di samping URL browser Anda dan ubah izin Lokasi menjadi "Allow / Izinkan".';
                    } else if (error.code === 2) { // POSITION_UNAVAILABLE
                        msg = 'Sinyal lokasi/GPS tidak tersedia. Pastikan fitur Lokasi (Location Services) di perangkat Anda aktif.';
                    } else if (error.code === 3) { // TIMEOUT
                        msg = 'Waktu pencarian GPS habis (Timeout). Pastikan koneksi internet stabil dan silakan klik tombol "Refresh" lokasi sekali lagi.';
                    }
                    alert(msg);
                },

                async startCamera() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: 'user',
                                width: {
                                    ideal: 640
                                },
                                height: {
                                    ideal: 480
                                }
                            },
                            audio: false
                        });
                        this.$refs.videoElement.srcObject = this.stream;
                        this.isCameraOpen = true;
                    } catch (err) {
                        console.error('Camera access error:', err);
                        alert('Tidak dapat mengakses kamera. Pastikan izin kamera telah diizinkan pada browser.');
                    }
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.isCameraOpen = false;
                },

                captureSnapshot() {
                    const video = this.$refs.videoElement;
                    const canvas = this.$refs.canvasElement;
                    if (!video || !canvas) return;

                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;

                    const ctx = canvas.getContext('2d');
                    // Mirror image horizontally for natural selfie perspective
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                    this.selfiePreview = dataUrl;
                    this.hasSelfie = true;

                    // Set Livewire property
                    this.$wire.set('selfie', dataUrl);

                    this.stopCamera();
                },

                retakeSnapshot() {
                    this.hasSelfie = false;
                    this.selfiePreview = null;
                    this.$wire.set('selfie', null);
                    this.startCamera();
                }
            }));
        });
    </script>
</x-filament-panels::page>