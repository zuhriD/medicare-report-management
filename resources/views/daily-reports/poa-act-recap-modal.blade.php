<div x-data="{ copied: false }" class="space-y-4">
    <div class="max-h-[28rem] overflow-y-auto border border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 shadow-inner">
        <pre class="font-mono text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words leading-relaxed" id="poaActRecapText">{{ $recapText }}</pre>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button
            type="button"
            x-on:click="
                navigator.clipboard.writeText(document.getElementById('poaActRecapText').innerText);
                copied = true;
                setTimeout(() => copied = false, 2500);
            "
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-sm cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
            </svg>
            <span x-text="copied ? '✅ Copied to Clipboard!' : 'Copy Recap'"></span>
        </button>
    </div>
</div>
