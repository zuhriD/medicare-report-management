<div class="space-y-3">
    @php
        $dateObj = isset($selectedDate) ? \Illuminate\Support\Carbon::parse($selectedDate) : now();
        $dateStr = $dateObj->format('d/m/Y') . ' (' . $dateObj->format('l') . ')';
    @endphp
    
    <div class="text-center text-sm font-semibold mb-3 text-gray-900 dark:text-gray-100">
        <div>Plan Of Action</div>
        <div>Date: {{ $dateStr }}</div>
    </div>
    
    <div class="max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-4 bg-white dark:bg-gray-900">
        <pre class="font-mono text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words" id="recapContent">{{ $recapText }}</pre>
    </div>
</div>
