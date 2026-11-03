import assert from 'node:assert/strict';
import test from 'node:test';
import { refreshOverview, overviewRefreshDelay, shouldPauseOverviewRefresh } from '../../resources/js/admin/overview-refresh.js';

test('overview only replaces report fragments and never replaces editing forms', async () => {
    const replaced = [];
    const selectors = ['.growth-metrics', '[data-growth-alert]', '.growth-history-heading', '[data-growth-probe-list]'];
    const root = {
        dataset: { overviewUrl: '/geo_admin/analytics' },
        querySelector: (selector) => {
            assert.ok(selectors.includes(selector));
            return { contains: () => false, querySelectorAll: () => [], replaceWith: () => replaced.push(selector) };
        },
    };
    const incoming = { querySelector: () => ({ querySelectorAll: () => [] }) };
    const ok = await refreshOverview(root, {
        fetchPage: async (url, options) => {
            assert.equal(url, '/geo_admin/analytics');
            assert.equal(options.credentials, 'same-origin');
            return { ok: true, redirected: false, text: async () => '<report>' };
        },
        parsePage: () => ({ querySelector: () => incoming }),
    });
    assert.equal(ok, true);
    assert.deepEqual(replaced, selectors);
});

test('an expired login redirect leaves the existing page intact', async () => {
    const root = { dataset: { overviewUrl: '/report' }, querySelector: () => assert.fail('must not change the page') };
    assert.equal(await refreshOverview(root, { fetchPage: async () => ({ ok: true, redirected: true }) }), false);
});

test('a selection made while fetching prevents replacing the results', async () => {
    const doc = { hidden: false, getSelection: () => ({ isCollapsed: true }) };
    const root = {
        dataset: { overviewUrl: '/report' }, ownerDocument: doc,
        querySelector: () => null, contains: () => true,
    };
    assert.equal(await refreshOverview(root, {
        fetchPage: async () => {
            doc.getSelection = () => ({ isCollapsed: false, anchorNode: {} });
            return { ok: true, text: async () => '<report>' };
        },
        parsePage: () => ({ querySelector: () => ({ querySelector: () => assert.fail('must preserve selected text') }) }),
    }), false);
});

test('an expanded answer remains open after its report is updated', async () => {
    let reopened = false;
    let replaced = false;
    const oldItem = { dataset: { probeUuid: 'probe-1' }, querySelectorAll: () => [oldDetail] };
    const oldDetail = { closest: () => oldItem };
    const newDetail = { setAttribute: (name) => { assert.equal(name, 'open'); reopened = true; } };
    const newItem = { dataset: { probeUuid: 'probe-1' }, querySelectorAll: () => [newDetail] };
    const root = {
        dataset: { overviewUrl: '/report' },
        querySelector: (selector) => selector === '[data-growth-probe-list]' ? {
            contains: () => false,
            querySelectorAll: () => [oldDetail],
            replaceWith: () => { replaced = true; },
        } : null,
    };
    await refreshOverview(root, {
        fetchPage: async () => ({ ok: true, text: async () => '<report>' }),
        parsePage: () => ({ querySelector: () => ({ querySelector: () => ({ querySelectorAll: () => [newItem] }) }) }),
    });
    assert.equal(replaced, true);
    assert.equal(reopened, true);
});

test('background updates pause when hidden, selecting text or focusing results', () => {
    const root = { querySelector: () => ({ contains: () => false }), contains: () => true };
    assert.equal(shouldPauseOverviewRefresh(root, { hidden: true }), true);
    assert.equal(shouldPauseOverviewRefresh(root, { hidden: false, getSelection: () => ({ isCollapsed: false }) }), true);
    assert.equal(shouldPauseOverviewRefresh(root, { hidden: false, getSelection: () => ({ isCollapsed: true }) }), false);
    assert.equal(shouldPauseOverviewRefresh({ querySelector: () => ({ contains: () => true }) }, { hidden: false }), true);
});

test('pending jobs poll faster and completed results return to the normal interval', () => {
    assert.equal(overviewRefreshDelay({ querySelector: () => ({}) }), 4000);
    assert.equal(overviewRefreshDelay({ querySelector: () => null }), 30000);
});
