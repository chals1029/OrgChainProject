(() => {
    'use strict';
    const controllers = new Map();

    function buildUrl(form, overrides = {}) {
        const action = form.getAttribute('action') || window.location.href;
        const url = new URL(action, window.location.href);
        url.search = '';
        const formData = new FormData(form);
        for (const [key, value] of formData.entries()) {
            const text = String(value);
            if (text !== '') url.searchParams.set(key, text);
        }
        for (const [key, value] of Object.entries(overrides)) {
            if (value === null || value === '') url.searchParams.delete(key);
            else url.searchParams.set(key, String(value));
        }
        return url;
    }

    async function swap({ url, targets, loadingTargets = null, historyMode = 'replace', onAfterSwap = null }) {
        const targetList = (Array.isArray(targets) ? targets : [targets]).filter(Boolean);
        const loadingList = (loadingTargets ? (Array.isArray(loadingTargets) ? loadingTargets : [loadingTargets]) : targetList).filter(Boolean);
        const targetUrl = typeof url === 'string' ? new URL(url, window.location.href) : new URL(url.toString(), window.location.href);
        const key = targetList.join('|') || targetUrl.pathname;
        const prev = controllers.get(key);
        if (prev) prev.abort();
        const controller = new AbortController();
        controllers.set(key, controller);
        const loadingEls = loadingList.flatMap(sel => Array.from(document.querySelectorAll(sel)));
        loadingEls.forEach(el => el.classList.add('org-smooth-loading'));

        try {
            const response = await fetch(targetUrl.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const finalUrl = new URL(response.url, window.location.href);
            if (finalUrl.pathname !== targetUrl.pathname) {
                window.location.assign(finalUrl.toString());
                return false;
            }
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            for (const sel of targetList) {
                if (!document.querySelector(sel) || !doc.querySelector(sel)) {
                    window.location.assign(targetUrl.toString());
                    return false;
                }
            }
            targetList.forEach(sel => {
                const cur = document.querySelector(sel);
                const next = doc.querySelector(sel);
                if (!cur || !next) return;
                cur.replaceChildren(...next.childNodes);
                cur.classList.remove('org-smooth-reveal');
                void cur.offsetWidth;
                cur.classList.add('org-smooth-reveal');
                cur.addEventListener('animationend', () => cur.classList.remove('org-smooth-reveal'), { once: true });
            });
            if (historyMode === 'push') {
                window.history.pushState(window.history.state, '', targetUrl.toString());
            } else if (historyMode === 'replace') {
                window.history.replaceState(window.history.state, '', targetUrl.toString());
            }
            if (typeof onAfterSwap === 'function') onAfterSwap(doc, targetUrl);
            return true;
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(targetUrl.toString());
            return false;
        } finally {
            if (controllers.get(key) === controller) {
                controllers.delete(key);
                loadingEls.forEach(el => el.classList.remove('org-smooth-loading'));
            }
        }
    }

    window.OrgSmoothFilter = { buildUrl, swap };
})();
