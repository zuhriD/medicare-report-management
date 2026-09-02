<div class="space-y-3">
    @php
        $today = now();
        $dateStr = $today->format('d/m/Y') . ' (' . $today->format('l') . ')';
    @endphp
    
    <div class="text-center text-sm font-semibold mb-3 text-gray-900 dark:text-gray-100">
        <div>DAILY ACHIEVEMENT & DEVELOPMENT REPORT</div>
        <div>Date: {{ $dateStr }}</div>
    </div>
    
    <div class="max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded p-4 bg-white dark:bg-gray-900">
        <pre class="font-mono text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words" id="recapContent">{{ $recapText }}</pre>
    </div>
    
    <button 
        type="button"
        onclick="copyRecapText()"
        class="w-full px-4 py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700 transition">
        Copy
    </button>
</div>

<script>
function copyRecapText() {
    const preElement = document.getElementById('recapContent');
    if (!preElement) {
        alert('Error: Could not find content to copy');
        return;
    }
    
    const text = preElement.innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Copied to clipboard!');
        // Close modal
        setTimeout(() => {
            const closeBtn = document.querySelector('[aria-label="Close"]');
            if (closeBtn) closeBtn.click();
        }, 500);
    }).catch((err) => {
        console.error('Copy failed:', err);
        alert('❌ Failed to copy text. Please try again.');
    });
}
</script>
