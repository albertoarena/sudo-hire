import type { Counts, JobState, ListView, Source, Status } from './types';

/** The PHP server does not answer: the app stopped in the background. */
export class ServerStopped extends Error {}

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
        throw new Error(`${method} ${path} failed with ${response.status}`);
    }

    return response.status === 204 ? (undefined as T) : ((await response.json()) as T);
}

export const api = {
    list: () => call<ListView>('GET', '/api/list'),
    status: () => call<Status>('GET', '/api/status'),
    sources: () => call<Source[]>('GET', '/api/sources'),
    setState: (id: number, state: JobState | null) => call<{ counts: Counts }>('PUT', `/api/jobs/${id}/state`, { state }),
    openUrl: (url: string) => call<void>('POST', '/api/app/open-url', { url }),
    openSettings: () => call<void>('POST', '/api/app/settings'),
    quit: () => call<void>('POST', '/api/app/quit'),
};
