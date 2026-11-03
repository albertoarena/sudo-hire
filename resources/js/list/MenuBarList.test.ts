import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { source } from '../settings/fixtures';
import { row, sources, status, view } from './fixtures';
import MenuBarList from './MenuBarList.vue';
import type { ListView, Source } from '../shared/types';

type Call = { method: string; path: string; body: unknown };

enableAutoUnmount(afterEach);

let calls: Call[];
let current: ListView;
let currentSources: Source[];

function fakeServer(): void {
    vi.stubGlobal(
        'fetch',
        vi.fn(async (path: string, init: RequestInit) => {
            const method = init.method ?? 'GET';
            calls.push({ method, path, body: init.body ? JSON.parse(init.body as string) : undefined });

            const json = (data: unknown) => new Response(JSON.stringify(data), { status: 200 });

            if (path === '/api/list') return json(current);
            if (path === '/api/status') return json(status);
            if (path === '/api/sources') return json(currentSources);
            if (path.startsWith('/api/jobs/')) return json({ counts: current.counts });

            return new Response(null, { status: 204 });
        }),
    );
}

async function mountList() {
    const wrapper = mount(MenuBarList);
    await flushPromises();

    return wrapper;
}

beforeEach(() => {
    calls = [];
    current = view();
    currentSources = sources;
    fakeServer();
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

describe('MenuBarList', () => {
    it('shows the strip, the tabs with counts, the rows and the scan times', async () => {
        current = view({ new: [row(), row({ id: 8, seen: true })], saved: [row({ id: 9, state: 'saved', seen: true })] });
        const wrapper = await mountList();

        expect(wrapper.find('.strip .count').text()).toBe('1 unseen');
        expect(wrapper.findAll('[role="tab"]').map((tab) => tab.text())).toEqual(['New2', 'Saved1', 'Applied0']);
        expect(wrapper.find('[role="tab"][aria-selected="true"]').text()).toBe('New2');
        expect(wrapper.findAll('.job')).toHaveLength(2);
        expect(wrapper.find('.bar').text()).toMatch(/Last scan \d\d:\d\d, next \d\d:\d\d/);
    });

    it('switches tabs', async () => {
        current = view({ saved: [row({ id: 9, title: 'Saved job', state: 'saved' })] });
        const wrapper = await mountList();

        await wrapper.findAll('[role="tab"]')[1].trigger('click');

        expect(wrapper.find('[role="tab"][aria-selected="true"]').text()).toBe('Saved1');
        expect(wrapper.find('.job .t').text()).toBe('Saved job');
    });

    it('saves a job, shows the toast, and Undo moves it back', async () => {
        const wrapper = await mountList();

        await wrapper.find('.job .act').trigger('click');
        await flushPromises();

        expect(calls).toContainEqual({ method: 'PUT', path: '/api/jobs/7/state', body: { state: 'saved' } });
        expect(wrapper.find('.toast').classes()).toContain('on');
        expect(wrapper.find('.toast').text()).toContain('Saved');

        await wrapper.find('.toast button').trigger('click');
        await flushPromises();

        // The job had no state: pressing Saved again clears it.
        expect(calls.filter((call) => call.method === 'PUT')).toHaveLength(2);
        expect(calls.at(-4)).toEqual({ method: 'PUT', path: '/api/jobs/7/state', body: { state: 'saved' } });
        expect(wrapper.find('.toast').classes()).not.toContain('on');
    });

    it('hides a job with the "Not for me" toast', async () => {
        const wrapper = await mountList();

        await wrapper.findAll('.job .act')[1].trigger('click');
        await flushPromises();

        expect(calls).toContainEqual({ method: 'PUT', path: '/api/jobs/7/state', body: { state: 'hidden' } });
        expect(wrapper.find('.toast').text()).toContain('Hidden. You will not see this job again');
    });

    it('says "Moved back to New" when the active state is pressed again, and Undo sets it again', async () => {
        current = view({ new: [], saved: [row({ state: 'saved' })] });
        const wrapper = await mountList();
        await wrapper.findAll('[role="tab"]')[1].trigger('click');

        await wrapper.find('.job .act').trigger('click');
        await flushPromises();
        expect(wrapper.find('.toast').text()).toContain('Moved back to New');

        await wrapper.find('.toast button').trigger('click');
        await flushPromises();
        expect(calls.filter((call) => call.method === 'PUT').map((call) => call.body)).toEqual([{ state: 'saved' }, { state: 'saved' }]);
    });

    it('hides the toast after 3.2 seconds', async () => {
        vi.useFakeTimers();
        const wrapper = await mountList();

        await wrapper.find('.job .act').trigger('click');
        await flushPromises();
        expect(wrapper.find('.toast').classes()).toContain('on');

        vi.advanceTimersByTime(3200);
        await flushPromises();
        expect(wrapper.find('.toast').classes()).not.toContain('on');
    });

    it('opens the apply link through the app, never in the web view', async () => {
        const open = vi.spyOn(window, 'open');
        const wrapper = await mountList();

        await wrapper.find('.job .btn').trigger('click');
        await flushPromises();

        expect(calls).toContainEqual({ method: 'POST', path: '/api/app/open-url', body: { url: 'https://example.com/jobs/7/apply' } });
        expect(open).not.toHaveBeenCalled();
    });

    it('expands and collapses the "Location not stated" group', async () => {
        current = view({ locationNotStated: [row({ id: 20, title: 'Somewhere job', stamp: { kind: 'not_stated', place: 'Location' } })] });
        const wrapper = await mountList();
        const group = wrapper.find('.group');

        expect(group.text()).toContain('Location not stated: 1 job');
        expect(group.attributes('aria-expanded')).toBe('false');
        expect(wrapper.text()).not.toContain('Somewhere job');

        await group.trigger('click');
        expect(group.attributes('aria-expanded')).toBe('true');
        expect(wrapper.find('.group-note').text()).toContain('They are never in a notification.');
        expect(wrapper.text()).toContain('Somewhere job');

        await group.trigger('click');
        expect(group.attributes('aria-expanded')).toBe('false');
    });

    it('shows the empty state of each tab', async () => {
        current = view({ new: [] });
        const wrapper = await mountList();

        expect(wrapper.find('.empty').text()).toMatch(/Nothing new since \d\d:\d\d/);
        expect(wrapper.find('.empty').text()).toContain('You get one notification when new jobs match.');

        await wrapper.findAll('[role="tab"]')[1].trigger('click');
        expect(wrapper.find('.empty').text()).toContain('No saved jobs');

        await wrapper.findAll('[role="tab"]')[2].trigger('click');
        expect(wrapper.find('.empty').text()).toContain('No applications yet');
    });

    it('shows the source warning and opens Sources from it', async () => {
        currentSources = [source({ key: 'weworkremotely', name: 'We Work Remotely', lastError: { message: 'cURL error 60', kind: 'certificate' } })];
        const wrapper = await mountList();

        expect(wrapper.find('.warn').text()).toContain('We Work Remotely: certificate error');

        await wrapper.find('.warn').trigger('click');
        await flushPromises();
        expect(calls).toContainEqual({ method: 'POST', path: '/api/app/settings', body: { pane: 'sources' } });
    });

    it('opens the settings and quits from the status bar', async () => {
        const wrapper = await mountList();
        const [settings, quit] = wrapper.findAll('.bar .act');

        await settings.trigger('click');
        await quit.trigger('click');
        await flushPromises();

        expect(calls.map((call) => call.path)).toEqual(expect.arrayContaining(['/api/app/settings', '/api/app/quit']));
    });

    it('shows the stopped state when the server does not answer, and quits through Electron', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => Promise.reject(new TypeError('Failed to fetch'))));
        const contextMenu = vi.fn();
        window.Native = { contextMenu };
        const wrapper = await mountList();

        expect(wrapper.find('.stopped h1').text()).toBe('Sudo Hire stopped in the background');
        expect(wrapper.find('[role="tablist"]').exists()).toBe(false);
        expect(wrapper.find('.foot').exists()).toBe(false);

        await wrapper.find('.stopped .btn').trigger('click');
        expect(contextMenu).toHaveBeenCalledWith([{ label: 'Quit Sudo Hire', role: 'quit' }]);

        delete window.Native;
    });

    it('reloads the list when the popover gets the focus', async () => {
        await mountList();
        const before = calls.filter((call) => call.path === '/api/list').length;

        window.dispatchEvent(new Event('focus'));
        await flushPromises();

        expect(calls.filter((call) => call.path === '/api/list').length).toBe(before + 1);
    });
});
