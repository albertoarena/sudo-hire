import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { settings } from '../settings/fixtures';
import type { Settings } from '../settings/types';
import FirstRun from './FirstRun.vue';
import type { FirstScan } from './types';

type Call = { method: string; path: string; body: unknown };

enableAutoUnmount(afterEach);

let calls: Call[];
let stored: Settings;
let preset: string | null;
let refuse: string | null;
let scans: FirstScan[];
let notificationStatus: number;

const countries = [
    { code: 'DE', name: 'Germany' },
    { code: 'IT', name: 'Italy' },
];

function scan(statuses: [string, string][]): FirstScan {
    const names: Record<string, string> = { ats: 'Company careers', larajobs: 'LaraJobs', himalayas: 'Himalayas' };

    return {
        sources: statuses.map(([key, status]) => ({ key, name: names[key], status: status as FirstScan['sources'][number]['status'], errorKind: status === 'failed' ? 'http' : null })),
        finished: statuses.every(([, status]) => status === 'done' || status === 'failed'),
    };
}

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
            if (path === '/api/country-preset') return json({ country: preset });
            if (path.startsWith('/api/stamp-preview')) return json({ jobs: [] });
            if (path === '/api/first-scan' && method === 'POST') return json(scans.length > 1 ? scans.shift() : scans[0], 202);
            if (path === '/api/first-scan') return json(scans.length > 1 ? scans.shift() : scans[0]);
            if (path === '/api/app/test-notification') return new Response(null, { status: notificationStatus });

            return new Response(null, { status: 204 });
        }),
    );
}

async function mountFirstRun() {
    const wrapper = mount(FirstRun, { attachTo: document.body });
    await flushPromises();

    return wrapper;
}

type Wrapper = Awaited<ReturnType<typeof mountFirstRun>>;

const heading = (wrapper: Wrapper) => wrapper.find('h1').text();
const primary = (wrapper: Wrapper) => wrapper.find('.nav .btn.primary');
const back = (wrapper: Wrapper) => wrapper.findAll('.nav .btn').find((button) => button.text() === 'Back');

async function next(wrapper: Wrapper) {
    await primary(wrapper).trigger('click');
    await flushPromises();
}

beforeEach(() => {
    calls = [];
    stored = settings({ country: null, keywordsInclude: [], workModes: ['remote'] });
    preset = 'IT';
    refuse = null;
    scans = [scan([['ats', 'waiting'], ['larajobs', 'waiting']])];
    notificationStatus = 204;
    fakeServer();
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

describe('FirstRun', () => {
    it('starts on Welcome with the tagline, Start and no Back', async () => {
        const wrapper = await mountFirstRun();

        expect(heading(wrapper)).toBe('Jobs you can take, from where you live');
        expect(wrapper.find('.cmdline').text()).toBe('$ sudo hire --remote-from here');
        expect(primary(wrapper).text()).toBe('Start');
        expect(back(wrapper)!.isVisible()).toBe(false);
        expect(wrapper.find('.nav .sr').text()).toBe('Step 1 of 4');
        expect(wrapper.findAll('.steps i.on')).toHaveLength(1);
    });

    it('walks through the four screens with Next and Back', async () => {
        const wrapper = await mountFirstRun();

        await next(wrapper);
        expect(heading(wrapper)).toBe('Where do you work from?');
        expect(primary(wrapper).text()).toBe('Next');
        expect(back(wrapper)!.isVisible()).toBe(true);
        expect(wrapper.find('.nav .sr').text()).toBe('Step 2 of 4');
        expect(wrapper.findAll('.steps i.on')).toHaveLength(2);

        await next(wrapper);
        expect(heading(wrapper)).toBe('What do you look for?');
        expect(primary(wrapper).text()).toBe('Run first scan');

        await back(wrapper)!.trigger('click');
        expect(heading(wrapper)).toBe('Where do you work from?');
    });

    it('moves the focus to the heading of each new screen', async () => {
        const wrapper = await mountFirstRun();

        await next(wrapper);

        expect(document.activeElement).toBe(wrapper.find('h1').element);
    });

    it('presets the country of the Mac without storing it', async () => {
        const wrapper = await mountFirstRun();
        await next(wrapper);

        expect((wrapper.find('#fr-country').element as HTMLInputElement).value).toBe('Italy');
        expect(calls.filter((call) => call.method !== 'GET')).toEqual([]);
    });

    it('needs a country before Next', async () => {
        preset = null;
        const wrapper = await mountFirstRun();
        await next(wrapper);

        await next(wrapper);

        expect(heading(wrapper)).toBe('Where do you work from?');
        expect(wrapper.find('#country-error').text()).toBe('Choose the country you work from.');
        expect(wrapper.find('#fr-country').attributes('aria-invalid')).toBe('true');
    });

    it('saves the country, keywords and work modes in one PUT, then starts the first scan', async () => {
        const wrapper = await mountFirstRun();
        await next(wrapper);
        await next(wrapper);

        const chip = wrapper.find('.chips input');
        await chip.setValue('Laravel');
        await chip.trigger('keydown', { key: 'Enter' });
        const hybrid = wrapper.findAll('input[type="checkbox"]').find((box) => box.element.parentElement!.textContent!.includes('Hybrid'));
        await hybrid!.setValue(true);

        await next(wrapper);

        const writes = calls.filter((call) => call.method !== 'GET');
        expect(writes).toEqual([
            { method: 'PUT', path: '/api/settings', body: { country: 'IT', keywordsInclude: ['laravel'], workModes: ['remote', 'hybrid'] } },
            { method: 'POST', path: '/api/first-scan', body: undefined },
        ]);
        expect(heading(wrapper)).toBe('First scan');
        expect(primary(wrapper).text()).toBe('Open the list');
    });

    it('stays on the keywords screen and shows the 422 message', async () => {
        refuse = 'Choose at least one work mode.';
        const wrapper = await mountFirstRun();
        await next(wrapper);
        await next(wrapper);

        await next(wrapper);

        expect(heading(wrapper)).toBe('What do you look for?');
        expect(wrapper.find('[role="alert"]').text()).toBe('Choose at least one work mode.');
        expect(calls.some((call) => call.path === '/api/first-scan')).toBe(false);
    });

    it('shows each source move from waiting to reading to done', async () => {
        vi.useFakeTimers();
        scans = [
            scan([['ats', 'waiting'], ['larajobs', 'waiting'], ['himalayas', 'waiting']]),
            scan([['ats', 'done'], ['larajobs', 'reading'], ['himalayas', 'waiting']]),
            scan([['ats', 'done'], ['larajobs', 'done'], ['himalayas', 'failed']]),
        ];
        const wrapper = await mountFirstRun();
        await next(wrapper);
        await next(wrapper);
        await next(wrapper);

        const rows = () => wrapper.findAll('.scanlist li').map((row) => row.text());
        expect(rows()).toEqual(['Company careerswaiting', 'LaraJobswaiting', 'Himalayaswaiting']);

        await vi.advanceTimersByTimeAsync(1000);
        expect(rows()).toEqual(['Company careersdone', 'LaraJobsreading', 'Himalayaswaiting']);
        expect(wrapper.findAll('.scanlist li.ok')).toHaveLength(1);

        await vi.advanceTimersByTimeAsync(1000);
        expect(rows()).toEqual(['Company careersdone', 'LaraJobsdone', 'Himalayasthe site did not answer']);

        // The scan is finished: no more requests.
        const polls = calls.filter((call) => call.path === '/api/first-scan').length;
        await vi.advanceTimersByTimeAsync(5000);
        expect(calls.filter((call) => call.path === '/api/first-scan')).toHaveLength(polls);
    });

    it('sends a test notification from the last screen, so macOS asks for permission', async () => {
        const wrapper = await mountFirstRun();
        await next(wrapper);
        await next(wrapper);
        await next(wrapper);

        const button = wrapper.findAll('.fr-notify .btn').find((item) => item.text() === 'Send a test notification');
        expect(wrapper.find('.fr-notify h2').text()).toBe('Notifications');
        expect(wrapper.find('.fr-notify p').text()).toContain('choose Allow');

        await button!.trigger('click');
        await flushPromises();

        expect(calls.at(-1)).toEqual({ method: 'POST', path: '/api/app/test-notification', body: undefined });
        expect(wrapper.find('.fr-notify .hint').text()).toBe('Sent. If no notification shows, open System Settings, Notifications, Sudo Hire, and allow notifications.');
    });

    it('says where to allow notifications when the test notification fails', async () => {
        notificationStatus = 502;
        const wrapper = await mountFirstRun();
        await next(wrapper);
        await next(wrapper);
        await next(wrapper);

        await wrapper.find('.fr-notify .btn').trigger('click');
        await flushPromises();

        expect(wrapper.find('.fr-notify .hint').text()).toBe('Sudo Hire could not send it. Open System Settings, Notifications, Sudo Hire, and allow notifications.');
        expect(wrapper.find('.settings-stopped').exists()).toBe(false);
    });

    it('opens the list from the last screen', async () => {
        const wrapper = await mountFirstRun();
        await next(wrapper);
        await next(wrapper);
        await next(wrapper);

        await next(wrapper);

        expect(calls.at(-1)).toEqual({ method: 'POST', path: '/api/app/open-list', body: undefined });
    });
});
