<div x-data="{
    copied: false,
    copyToClipboard() {
        const text = document.getElementById('poaActRecapText').innerText;
        navigator.clipboard.writeText(text).then(() => {
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        });
    },
    exportTxt() {
        const text = document.getElementById('poaActRecapText').innerText;
        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'POA_ACT_Recap_{{ $dbDate ?? date('Y-m-d') }}.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },
    printRecap() {
        const text = document.getElementById('poaActRecapText').innerText;
        const win = window.open('', '_blank');
        win.document.write('<!DOCTYPE html><html><head><title>POA & ACT Recap</title><style>body { font-family: monospace; font-size: 13px; line-height: 1.6; white-space: pre-wrap; padding: 24px; color: #111; }</style></head><body>' + text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</body></html>');
        win.document.close();
        win.focus();
        setTimeout(() => win.print(), 250);
    }
}" class="space-y-4">
    <div class="max-h-[30rem] overflow-y-auto border border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 shadow-inner">
        <pre class="font-mono text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words leading-relaxed" id="poaActRecapText">{{ $recapText }}</pre>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
        <div class="flex items-center gap-2">
            <button
                type="button"
                x-on:click="copyToClipboard()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                </svg>
                <span x-text="copied ? '✅ Copied to Clipboard!' : 'Copy to Clipboard'"></span>
            </button>

            <button
                type="button"
                x-on:click="exportTxt()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span>Export (.txt)</span>
            </button>

            <button
                type="button"
                x-on:click="printRecap()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                <span>Print</span>
            </button>
        </div>
    </div>
</div>
