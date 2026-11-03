export const overviewRefreshDelay = (root) => root.querySelector('[data-probe-status="queued"], [data-probe-status="running"]') ? 4000 : 30000;

export const shouldPauseOverviewRefresh = (root, doc = document) => {
    if (doc.hidden) return true;
    if (root.querySelector('[data-growth-probe-list]')?.contains(doc.activeElement)) return true;
    const selection = doc.getSelection?.();
    return Boolean(selection && !selection.isCollapsed && root.contains(selection.anchorNode));
};

export async function refreshOverview(root, { fetchPage = fetch, parsePage = (html) => new DOMParser().parseFromString(html, 'text/html') } = {}) {
    const response = await fetchPage(root.dataset.overviewUrl, {
        credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'text/html' }, signal: AbortSignal.timeout(15000),
    });
    if (!response.ok || response.redirected) return false;
    const page = parsePage(await response.text());
    const incoming = page.querySelector('[data-overview-refresh]');
    if (!incoming) return false;
    // The user may start reading or interacting while the request is in flight.
    if (root.ownerDocument && shouldPauseOverviewRefresh(root, root.ownerDocument)) return false;
    const selectors = ['.growth-metrics', '[data-growth-alert]', '.growth-history-heading', '[data-growth-probe-list]'];
    for (const selector of selectors) {
        const current = root.querySelector(selector);
        const updated = incoming.querySelector(selector);
        if (!current || !updated || current.contains(root.ownerDocument?.activeElement)) continue;
        if (selector === '[data-growth-probe-list]') {
            const opened = Array.from(current.querySelectorAll('details[open]')).map((detail) => {
                const item = detail.closest('[data-probe-uuid]');
                return [item?.dataset.probeUuid, Array.from(item?.querySelectorAll('details') || []).indexOf(detail)];
            });
            for (const [uuid, index] of opened) {
                Array.from(updated.querySelectorAll('[data-probe-uuid]')).find((item) => item.dataset.probeUuid === uuid)
                    ?.querySelectorAll('details')[index]?.setAttribute('open', '');
            }
        }
        current.replaceWith(updated);
    }
    globalThis.lucide?.createIcons();
    return true;
}

export function initializeOverviewRefresh(root) {
    let busy = false;
    const button = root.querySelector('[data-analytics-refresh]');
    const update = async () => {
        if (busy) return;
        busy = true;
        if (button) button.disabled = true;
        try {
            await refreshOverview(root);
        } catch { /* A temporary network failure must not interrupt editing. */ }
        finally {
            busy = false;
            if (button) button.disabled = false;
        }
    };
    button?.addEventListener('click', update);
    const tick = async () => {
        if (!root.isConnected) return;
        if (!shouldPauseOverviewRefresh(root)) await update();
        window.setTimeout(tick, overviewRefreshDelay(root));
    };
    window.setTimeout(tick, overviewRefreshDelay(root));
    if (new URLSearchParams(window.location.search).get('probe')) {
        root.querySelector('[data-growth-probe]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
