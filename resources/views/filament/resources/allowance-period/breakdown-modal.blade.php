<div class="space-y-4">
    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex justify-between items-center text-sm">
        <div>
            <span class="text-gray-500 dark:text-gray-400">Staff:</span>
            <strong class="text-gray-900 dark:text-gray-100 font-semibold">{{ $staffRecord->user?->name }}</strong>
            <span class="text-xs text-gray-500">({{ $staffRecord->user?->office?->name ?? 'No Office' }})</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Total Allowance:</span>
            <strong class="text-primary-600 dark:text-primary-400 font-bold text-base">{{ $staffRecord->formatted_total_allowance }}</strong>
        </div>
    </div>

    @php
        $attendanceItems = $staffRecord->items->where('item_type', 'attendance');
        $overtimeItems = $staffRecord->items->where('item_type', 'overtime');
    @endphp

    <!-- Attendance Items -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="bg-blue-50/50 dark:bg-blue-950/30 px-4 py-2 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h4 class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Rincian Uang Kehadiran ({{ $attendanceItems->count() }} Hari - {{ $staffRecord->formatted_attendance_amount }})
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Check In</th>
                        <th class="px-3 py-2">Check Out</th>
                        <th class="px-3 py-2">Durasi Kerja</th>
                        <th class="px-3 py-2 text-right">Nominal (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($attendanceItems as $item)
                        @php
                            $att = $item->attendance;
                            $hours = floor($item->duration_minutes / 60);
                            $mins = $item->duration_minutes % 60;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                            <td class="px-3 py-2 font-medium">{{ \Carbon\Carbon::parse($item->item_date)->translatedFormat('d M Y (D)') }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $att?->check_in_at?->format('H:i') ?? '-' }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $att?->check_out_at?->format('H:i') ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $hours }}j {{ $mins }}m</td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                                Rp {{ number_format((float) $item->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-3 text-center text-gray-400 italic">Tidak ada rekaman kehadiran eligible</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Overtime Items -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="bg-amber-50/50 dark:bg-amber-950/30 px-4 py-2 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h4 class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Rincian Uang Lembur ({{ round($staffRecord->total_overtime_minutes / 60, 1) }} Jam - {{ $staffRecord->formatted_overtime_amount }})
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Mulai OT</th>
                        <th class="px-3 py-2">Selesai OT</th>
                        <th class="px-3 py-2">Durasi Lembur</th>
                        <th class="px-3 py-2 text-right">Nominal (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($overtimeItems as $item)
                        @php
                            $ot = $item->overtime;
                            $hours = floor($item->duration_minutes / 60);
                            $mins = $item->duration_minutes % 60;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                            <td class="px-3 py-2 font-medium">{{ \Carbon\Carbon::parse($item->item_date)->translatedFormat('d M Y (D)') }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $ot?->check_in_at?->format('H:i') ?? '-' }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $ot?->check_out_at?->format('H:i') ?? '-' }}</td>
                            <td class="px-3 py-2 font-medium text-amber-600 dark:text-amber-400">{{ $hours }}j {{ $mins }}m</td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                                Rp {{ number_format((float) $item->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-3 text-center text-gray-400 italic">Tidak ada rekaman lembur eligible</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
