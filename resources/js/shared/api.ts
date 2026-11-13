import type { FirstScan } from '../first-run/types';
import type { Company, HiddenJobs, Rates, Settings } from '../settings/types';
import type { Counts, Country, JobState, ListView, PreviewJob, Source, Status } from './types';

/** The PHP server does not answer: the app stopped in the background. */
export class ServerStopped extends Error {}

/** The API refused the request. The message is the plain `message` of the API, for example a 422. */
export class ApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
    ) {
        super(message);
    }
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function call<T>(method: string, path: string, body?: unknown): Promise<T> {
    let response: Response;

    try {
        response = await fetch(path, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
    } catch {
        // fetch throws only when nothing answers.
        throw new ServerStopped();
    }

    if (!response.ok) {
        const message = await response
            .json()
            .then((data: { message?: unknown }) => (typeof data.message === 'string' ? data.message : null))
            .catch(() => null);

        throw new ApiError(response.status, message ?? `${method} ${path} failed with ${response.status}`);
    }

    return response.status === 204 ? (undefined as T) : ((await response.json()) as T);
}

export const api = {
    list: () => call<ListView>('GET', '/api/list'),
    status: () => call<Status>('GET', '/api/status'),
    sources: () => call<Source[]>('GET', '/api/sources'),
    setState: (id: number, state: JobState | null) => call<{ counts: Counts }>('PUT', `/api/jobs/${id}/state`, { state }),
    openUrl: (url: string) => call<void>('POST', '/api/app/open-url', { url }),
    openSettings: (pane?: string) => call<void>('POST', '/api/app/settings', pane === undefined ? undefined : { pane }),
    quit: () => call<void>('POST', '/api/app/quit'),

    settings: () => call<Settings>('GET', '/api/settings'),
    saveSettings: (patch: Partial<Settings>) => call<Settings>('PUT', '/api/settings', patch),
    countries: () => call<Country[]>('GET', '/api/countries'),
    stampPreview: (country: string) => call<{ jobs: PreviewJob[] }>('GET', `/api/stamp-preview?country=${encodeURIComponent(country)}`),
    rates: () => call<Rates>('GET', '/api/rates'),
    companies: () => call<Company[]>('GET', '/api/companies'),
    addCompany: (provider: string, slug: string) => call<Company>('POST', '/api/companies', { provider, slug }),
    removeCompany: (id: number) => call<void>('DELETE', `/api/companies/${id}`),
    hiddenJobs: () => call<HiddenJobs>('GET', '/api/hidden-jobs'),
    clearOlderHiddenJobs: () => call<void>('DELETE', '/api/hidden-jobs/older'),
    settingsTitle: (pane: string) => call<void>('POST', '/api/app/settings-title', { pane }),
    showDataFolder: () => call<void>('POST', '/api/app/show-data-folder'),
    openLogFolder: () => call<void>('POST', '/api/app/open-log-folder'),
    copyDiagnostics: () => call<void>('POST', '/api/app/copy-diagnostics'),

    countryPreset: () => call<{ country: string | null }>('GET', '/api/country-preset'),
    startFirstScan: () => call<FirstScan>('POST', '/api/first-scan'),
    firstScan: () => call<FirstScan>('GET', '/api/first-scan'),
    openList: () => call<void>('POST', '/api/app/open-list'),
    testNotification: () => call<void>('POST', '/api/app/test-notification'),
};
