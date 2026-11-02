import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ISSUE_URL, PRIVACY_URL } from '../shared/links';
import type { Source } from '../shared/types';
import { company, countries, hiddenJobs, settings, source } from './fixtures';
import SettingsWindow from './SettingsWindow.vue';
import type { Company, HiddenJobs, Settings } from './types';

type Call = { method: string; path: string; body: unknown };

enableAutoUnmount(afterEach);

let calls: Call[];
let stored: Settings;
let sources: Source[];
let companies: Company[];
let hidden: HiddenJobs;
let refuse: string | null;

function fakeServer(): void {
    vi.stubGlobal(
        'fetch',
        vi.fn(async (path: string, init: RequestInit) => {
            const method = init.method ?? 'GET';
            const body = init.body ? JSON.parse(init.body as string) : undefined;
            calls.push({ method, path, body });

            const json = (data: unknown, status = 200) => new Response(JSON.stringify(data), { status });

            if (path === '/api/settings' && method === 'GET') return json(stored);
            if (path === '/api/settings' && method === 'PUT') {
                if (refuse) return json({ message: refuse }, 422);
                stored = { ...stored, ...body };

                return json(stored);
            }
            if (path === '/api/countries') return json(countries);
            if (path === '/api/sources') return json(sources);
            if (path === '/api/companies' && method === 'GET') return json(companies);
            if (path === '/api/companies' && method === 'POST') {
                if (body.slug === 'nope') return json({ message: 'No public job board for lever:nope.' }, 422);
                companies = [...companies, company({ id: 2, provider: body.provider, slug: body.slug, name: 'Globex', lastCheckedAt: null })];

                return json(companies[1], 201);
            }
            if (path === '/api/rates') return json({ date: '2026-10-29', source: 'European Central Bank' });
            if (path === '/api/hidden-jobs') return json(hidden);
            if (path.startsWith('/api/stamp-preview')) return json({ jobs: [] });
            if (path.startsWith('/api/jobs/')) {
                hidden = hiddenJobs({ jobs: [] });

                return json({ state: null, counts: {} });
            }

            return new Response(null, { status: 204 });
        }),
    );
}

async function mountWindow() {
    const wrapper = mount(SettingsWindow, { attachTo: document.body });
    await flushPromises();

    return wrapper;
}

async function openPane(wrapper: Awaited<ReturnType<typeof mountWindow>>, name: string) {
    const item = wrapper.findAll('nav button').find((button) => button.text() === name);
    await item!.trigger('click');
    await flushPromises();
}

const writes = () => calls.filter((call) => call.method !== 'GET');
const lastPut = () => calls.filter((call) => call.method === 'PUT' && call.path === '/api/settings').at(-1)?.body;

beforeEach(() => {
    calls = [];
    stored = settings();
    sources = [source(), source({ key: 'jobicy', name: 'Jobicy', jobCount: 0 })];
    companies = [company()];
    hidden = hiddenJobs();
    refuse = null;
    fakeServer();
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

describe('SettingsWindow', () => {
    it('opens on Where you work and sets the window title', async () => {
        const wrapper = await mountWindow();

        expect(wrapper.find('nav [aria-current="page"]').text()).toBe('Where you work');
        expect(wrapper.find('.pane h1').text()).toBe('Where you work');
        expect(writes()).toContainEqual({ method: 'POST', path: '/api/app/settings-title', body: { pane: 'where' } });
    });

    it('selects a pane from the sidebar and sets the title', async () => {
        const wrapper = await mountWindow();

        await openPane(wrapper, 'Jobs');

        expect(wrapper.find('nav [aria-current="page"]').text()).toBe('Jobs');
        expect(wrapper.findAll('nav [aria-current]')).toHaveLength(1);
        expect(wrapper.find('.pane h1').text()).toBe('Jobs');
        expect(document.title).toBe('Jobs');
        expect(writes().at(-1)).toEqual({ method: 'POST', path: '/api/app/settings-title', body: { pane: 'jobs' } });
    });

    it('shows a red dot on Sources when an enabled source fails', async () => {
        let wrapper = await mountWindow();
        expect(wrapper.find('nav .bad').exists()).toBe(false);
        wrapper.unmount();

        sources = [source({ lastError: { message: 'cURL error 60', kind: 'certificate' } })];
        wrapper = await mountWindow();

        const dot = wrapper.find('nav .bad');
        expect(dot.exists()).toBe(true);
        expect(dot.element.closest('button')!.textContent).toContain('Sources');
    });

    it('saves a checkbox when it changes', async () => {
        const wrapper = await mountWindow();

        await wrapper.find('input[type="checkbox"][value="hybrid"]').setValue(true);
        await flushPromises();

        expect(lastPut()).toEqual({ workModes: ['remote', 'hybrid'] });
    });

    it('saves a select when it changes', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Jobs');

        await wrapper.find('select#posted').setValue('336');
        await flushPromises();

        expect(lastPut()).toEqual({ postedWithinHours: 336 });
    });

    it('saves a text field on blur and on Enter, not on each key', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Pay');
        const field = wrapper.find('input#minyr');

        await field.setValue('60000');
        expect(lastPut()).toBeUndefined();

        await field.trigger('blur');
        await flushPromises();
        expect(lastPut()).toEqual({ minAnnualSalary: 60000 });

        await field.setValue('');
        await field.trigger('keydown', { key: 'Enter' });
        await flushPromises();
        expect(lastPut()).toEqual({ minAnnualSalary: null });
    });

    it('shows a 422 message under the field', async () => {
        const wrapper = await mountWindow();
        refuse = 'Select at least one work mode.';

        const remote = wrapper.find('input[type="checkbox"][value="remote"]');
        await remote.setValue(false);
        await flushPromises();

        const error = wrapper.find('#workModes-error');
        expect(error.text()).toBe('Select at least one work mode.');
        expect(error.attributes('role')).toBe('alert');
        expect((remote.element as HTMLInputElement).checked).toBe(true);

        refuse = null;
        await wrapper.find('input[type="checkbox"][value="hybrid"]').setValue(true);
        await flushPromises();
        expect(wrapper.find('#workModes-error').exists()).toBe(false);
    });

    it('saves a new country and shows its preview', async () => {
        const wrapper = await mountWindow();
        const input = wrapper.find('input#country');

        await input.trigger('focus');
        await input.setValue('germ');
        await input.trigger('keydown', { key: 'Enter' });
        await flushPromises();

        expect(lastPut()).toEqual({ country: 'DE' });
        expect(calls.map((call) => call.path)).toContain('/api/stamp-preview?country=DE');
    });

    it('adds and removes keywords', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Jobs');
        const include = wrapper.find('input[aria-label="Add a keyword"]');

        await include.setValue('Symfony');
        await include.trigger('keydown', { key: 'Enter' });
        await flushPromises();
        expect(lastPut()).toEqual({ keywordsInclude: ['laravel', 'php', 'symfony'] });

        await wrapper.find('button[aria-label="Remove php"]').trigger('click');
        await flushPromises();
        expect(lastPut()).toEqual({ keywordsInclude: ['laravel', 'symfony'] });
    });

    it('turns a source off with its switch', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Sources');

        const toggle = wrapper.find('input.switch[aria-label="Jobicy"]');
        expect((toggle.element as HTMLInputElement).checked).toBe(true);

        await toggle.setValue(false);
        await flushPromises();

        expect(lastPut()).toEqual({ disabledSources: ['jobicy'] });
    });

    it('shows the status of each source', async () => {
        sources = [source(), source({ key: 'wwr', name: 'We Work Remotely', lastError: { message: 'cURL error 60', kind: 'certificate' } })];
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Sources');

        const rows = wrapper.findAll('tbody tr');
        expect(rows[0].text()).toContain('5 jobs');
        expect(rows[1].find('.status.err').text()).toBe('Certificate error');
        expect(rows[1].text()).toContain('The secure connection failed.');
    });

    it('adds a company and shows the API error for an unknown slug', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Companies');

        await wrapper.find('select#provider').setValue('lever');
        await wrapper.find('input#slug').setValue('nope');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(writes().at(-1)).toEqual({ method: 'POST', path: '/api/companies', body: { provider: 'lever', slug: 'nope' } });
        expect(wrapper.find('#slug-error').text()).toBe('No public job board for lever:nope.');

        await wrapper.find('input#slug').setValue('globex');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(wrapper.find('#slug-error').exists()).toBe(false);
        expect((wrapper.find('input#slug').element as HTMLInputElement).value).toBe('');
        expect(wrapper.findAll('tbody tr')).toHaveLength(2);
    });

    it('removes a company', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Companies');

        companies = [];
        await wrapper.find('tbody button').trigger('click');
        await flushPromises();

        expect(writes().at(-1)).toEqual({ method: 'DELETE', path: '/api/companies/1', body: undefined });
        expect(wrapper.findAll('tbody tr')).toHaveLength(0);
    });

    it('shows a hidden job again', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Jobs');

        const item = wrapper.find('.hidden-jobs li');
        expect(item.text()).toContain('PHP Developer');
        expect(item.text()).toContain('Globex');

        await item.find('button').trigger('click');
        await flushPromises();

        expect(writes().at(-1)).toEqual({ method: 'PUT', path: '/api/jobs/31/state', body: { state: 'hidden' } });
        expect(wrapper.find('.hidden-jobs').text()).toContain('No hidden jobs.');
    });

    it('clears older hidden jobs after a confirmation in the page', async () => {
        hidden = hiddenJobs({ jobs: [], olderCount: 3 });
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Jobs');

        const clear = wrapper.findAll('.hidden-jobs button').find((button) => button.text() === 'Clear older hidden jobs (3)');
        await clear!.trigger('click');
        expect(writes().filter((call) => call.method === 'DELETE')).toEqual([]);

        hidden = hiddenJobs({ jobs: [], olderCount: 0 });
        const confirm = wrapper.findAll('.hidden-jobs button').find((button) => button.text() === 'Clear');
        await confirm!.trigger('click');
        await flushPromises();

        expect(writes().at(-1)).toEqual({ method: 'DELETE', path: '/api/hidden-jobs/older', body: undefined });
        expect(wrapper.find('.hidden-jobs').text()).not.toContain('Clear older hidden jobs');
    });

    it('has no clear button with no older hidden jobs', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Jobs');

        expect(wrapper.find('.hidden-jobs').text()).not.toContain('Clear older hidden jobs');
    });

    it('copies the diagnostic report and shows "Copied" for 1.6 seconds', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Troubleshooting');
        vi.useFakeTimers();

        const copy = () => wrapper.findAll('button').find((button) => ['Copy diagnostic report', 'Copied'].includes(button.text()))!;
        await copy().trigger('click');
        await flushPromises();

        expect(writes().at(-1)).toEqual({ method: 'POST', path: '/api/app/copy-diagnostics', body: undefined });
        expect(copy().text()).toBe('Copied');

        await vi.advanceTimersByTimeAsync(1600);
        expect(copy().text()).toBe('Copy diagnostic report');
    });

    it('opens the privacy policy and the issue page in the browser', async () => {
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Privacy');
        await wrapper.findAll('button').find((button) => button.text() === 'Read the privacy policy')!.trigger('click');
        await openPane(wrapper, 'Troubleshooting');
        await wrapper.findAll('button').find((button) => button.text() === 'Report a problem')!.trigger('click');
        await flushPromises();

        const opened = writes().filter((call) => call.path === '/api/app/open-url').map((call) => call.body);
        expect(opened).toEqual([{ url: PRIVACY_URL }, { url: ISSUE_URL }]);
    });

    it('opens Sources from the status note', async () => {
        sources = [source({ lastError: { message: 'cURL error 60', kind: 'certificate' } })];
        const wrapper = await mountWindow();
        await openPane(wrapper, 'Troubleshooting');

        expect(wrapper.find('.note').text()).toContain('LaraJobs: certificate error at');
        await wrapper.findAll('button').find((button) => button.text() === 'Show sources')!.trigger('click');
        await flushPromises();

        expect(wrapper.find('nav [aria-current="page"]').text()).toBe('Sources');
    });

    it('shows the stopped state when the server does not answer', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => Promise.reject(new TypeError('Failed to fetch'))));
        const wrapper = await mountWindow();

        expect(wrapper.text()).toContain('Sudo Hire stopped in the background');
    });
});
