document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('supportForm');
    const questionInput = document.getElementById('question');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const responseContainer = document.getElementById('responseContainer');
    

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const dotaz = questionInput.value.trim();
        const email = emailInput.value.trim();

        if (!dotaz) return;

        submitBtn.disabled = true;
        btnText.textContent = 'Odesílám...';

        responseContainer.innerHTML = `
            <div class="space-y-3">
                <div class="text-xs text-indigo-400 font-semibold uppercase tracking-wider">Tvůj dotaz:</div>
                <div class="text-slate-200 bg-slate-800 p-3 rounded-lg border border-slate-700/50">${escapeHtml(dotaz)}</div>
                <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider pt-1">Odpověď:</div>
                <div class="flex items-center gap-2 text-indigo-400 animate-pulse text-sm">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Generuji odpověď z Gemini...</span>
                </div>
            </div>
        `;

        try {
            // Nahraď URL svou skutečnou adresou z Renderu, např. https://muj-app.onrender.com/chat
            const res = await fetch('https://prg-2bmf.onrender.com/chat',  {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: dotaz, email: email })
            });

            const rawText = await res.text();

            let data;
            try {
                data = JSON.parse(rawText);
            } catch (parseError) {
                throw new Error("Server nevrátil JSON, ale: " + (rawText || "[prázdná odpověď serveru]"));
            }

            if (res.ok && data.reply) {
                const formattedAnswer = formatMarkdown(data.reply);
                responseContainer.innerHTML = `
                    <div class="space-y-3">
                        <div class="text-xs text-indigo-400 font-semibold uppercase tracking-wider">Tvůj dotaz:</div>
                        <div class="text-slate-200 bg-slate-800 p-3 rounded-lg border border-slate-700/50">${escapeHtml(dotaz)}</div>
                        <div class="text-xs text-emerald-400 font-semibold uppercase tracking-wider pt-1">Odpověď podpory:</div>
                        <div class="text-slate-100 leading-relaxed bg-slate-800/40 p-4 rounded-lg border border-slate-700/40">${formattedAnswer}</div>
                    </div>
                `;
            } else {
                throw new Error(data.error || 'Server nevrátil odpověď.');
            }

        } catch (err) {
            responseContainer.innerHTML = `
                <div class="text-rose-400 font-medium">⚠️ Chyba: ${escapeHtml(err.message)}</div>
            `;
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Odeslat dotaz';
        }
    });

    function formatMarkdown(text) {
        let safe = escapeHtml(text);
        safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        safe = safe.replace(/\*(.*?)\*/g, '<em>$1</em>');
        safe = safe.replace(/^[\-*]\s+(.*)$/gm, '<li class="ml-4 list-disc">$1</li>');
        safe = safe.replace(/\n/g, '<br>');
        return safe;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});