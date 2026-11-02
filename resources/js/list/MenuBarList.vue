<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { api, ServerStopped } from './api';
import Icon from './Icon.vue';
import JobRow from './JobRow.vue';
import { clock, sourceWarning } from './text';
import type { JobState, ListView, Row, Source, Status, Tab } from './types';

declare global {
    interface Window {
        Native?: { contextMenu: (template: { label?: string; role?: string }[]) => void };
    }
}

const view = ref<ListView | null>(null);
const status = ref<Status | null>(null);
const sources = ref<Source[]>([]);
const stopped = ref(false);
const tab = ref<Tab>('new');
const groupOpen = ref(false);
const listEl = ref<HTMLElement | null>(null);
const groupEl = ref<HTMLElement | null>(null);
const noteEl = ref<HTMLElement | null>(null);
const toast = ref<{ text: string; undo: (() => Promise<void>) | null } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;

const TABS: { key: Tab; label: string }[] = [
    { key: 'new', label: 'New' },
    { key: 'saved', label: 'Saved' },
    { key: 'applied', label: 'Applied' },
];

const rows = computed<Row[]>(() => view.value?.[tab.value] ?? []);
const warning = computed(() => sourceWarning(sources.value));
const groupCount = computed(() => view.value?.counts.locationNotStated ?? 0);

const scanLine = computed(() => {
    const last = status.value?.lastScan.finishedAt;
    const next = status.value?.nextScanAt;

    if (!last) {
        return 'The first scan runs within 5 minutes';
    }

    return next ? `Last scan ${clock(last)}, next ${clock(next)}` : `Last scan ${clock(last)}`;
});

const empty = computed(() => {
    if (tab.value === 'saved') {
        return ['No saved jobs', 'Save a job to keep it here after 30 days.'];
    }

    if (tab.value === 'applied') {
        return ['No applications yet', 'Mark a job as Applied to track it here.'];
    }

    const last = status.value?.lastScan.finishedAt;
    const next = status.value?.nextScanAt;

    return [
        last ? `Nothing new since ${clock(last)}` : 'Nothing new yet',
        `${next ? `Next scan at ${clock(next)}.` : 'The first scan runs within 5 minutes.'} You get one notification when new jobs match.`,
    ];
});

async function guard(work: () => Promise<void>): Promise<void> {
    try {
        await work();
        stopped.value = false;
    } catch (error) {
        if (error instanceof ServerStopped) {
            stopped.value = true;
        } else {
            console.error(error);
        }
    }
}

function load(): Promise<void> {
    return guard(async () => {
        const [list, scan, all] = await Promise.all([api.list(), api.status(), api.sources()]);
        view.value = list;
        status.value = scan;
        sources.value = all;
    });
}

function showToast(text: string, undo: (() => Promise<void>) | null = null): void {
    toast.value = { text, undo };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toast.value = null), 3200);
}

const WORDS: Record<JobState | 'new', string> = {
    saved: 'Saved',
    applied: 'Marked as applied',
    hidden: 'Hidden. You will not see this job again',
    new: 'Moved back to New',
};

// Pressing the active state again moves the job back to New (the API clears it).
function act(row: Row, state: JobState): Promise<void> {
    const before = row.state;
    const after = before === state ? null : state;

    return guard(async () => {
        await api.setState(row.id, state);
        await load();
        showToast(WORDS[after ?? 'new'], () =>
            guard(async () => {
                // Undo: set the old state again, or press the new one again to clear it.
                await api.setState(row.id, before ?? state);
                toast.value = null;
                await load();
            }),
        );
    });
}

// Opening the group scrolls it to the top of the list, so its jobs show without a scroll.
async function toggleGroup(): Promise<void> {
    groupOpen.value = !groupOpen.value;

    if (!groupOpen.value) {
        return;
    }

    await nextTick();
    const list = listEl.value;

    if (!list || !groupEl.value || !noteEl.value) {
        return;
    }

    const top = list.scrollTop + noteEl.value.getBoundingClientRect().top - list.getBoundingClientRect().top - groupEl.value.offsetHeight;
    const still = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    list.scrollTo?.({ top, behavior: still ? 'auto' : 'smooth' });
}

function apply(row: Row): Promise<void> {
    return guard(() => api.openUrl(row.applyUrl));
}

function quitWhenStopped(): void {
    // PHP is gone, so the API cannot quit. Electron's quit role still can.
    window.Native?.contextMenu([{ label: 'Quit Sudo Hire', role: 'quit' }]);
}

function onVisible(): void {
    if (document.visibilityState === 'visible') {
        void load();
    }
}

onMounted(() => {
    void load();
    window.addEventListener('focus', load);
    document.addEventListener('visibilitychange', onVisible);
});

onBeforeUnmount(() => {
    window.removeEventListener('focus', load);
    document.removeEventListener('visibilitychange', onVisible);
    clearTimeout(toastTimer);
});
</script>

<template>
    <div class="pop">
        <header class="strip">
            <span class="name">Sudo Hire</span>
            <span v-if="view && !stopped" class="count">{{ view.counts.unseen }} unseen</span>
        </header>

        <div v-if="stopped" class="stopped">
            <svg class="big" viewBox="0 0 16 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2.2 14.5 13.5h-13z" stroke-width="1.4" />
                <path d="M8 6.5v3.2M8 11.6v.1" stroke-width="1.6" />
            </svg>
            <h1>Sudo Hire stopped in the background</h1>
            <p>Quit Sudo Hire and open it again. Your saved jobs are safe.</p>
            <button type="button" class="btn primary" @click="quitWhenStopped">Quit Sudo Hire</button>
        </div>

        <template v-else-if="view">
            <div class="seg-wrap">
                <div class="seg" role="tablist" aria-label="Job lists">
                    <button
                        v-for="item in TABS"
                        :key="item.key"
                        type="button"
                        role="tab"
                        :aria-selected="tab === item.key"
                        @click="tab = item.key"
                    >
                        {{ item.label }}<span class="n">{{ view.counts[item.key] }}</span>
                    </button>
                </div>
            </div>

            <div ref="listEl" class="list" role="tabpanel">
                <JobRow v-for="row in rows" :key="row.id" :row="row" @act="act" @apply="apply" />

                <div v-if="rows.length === 0" class="empty">
                    <span class="big">{{ empty[0] }}</span>
                    <span>{{ empty[1] }}</span>
                </div>

                <template v-if="tab === 'new' && groupCount > 0">
                    <button ref="groupEl" type="button" class="group" :aria-expanded="groupOpen" @click="toggleGroup">
                        Location not stated: {{ groupCount }} {{ groupCount === 1 ? 'job' : 'jobs' }}<Icon name="chev" />
                    </button>
                    <template v-if="groupOpen">
                        <p ref="noteEl" class="group-note">These jobs do not say where you can work from. Check before you apply. They are never in a notification.</p>
                        <JobRow v-for="row in view.locationNotStated" :key="row.id" :row="row" group @act="act" @apply="apply" />
                    </template>
                </template>
            </div>

            <div class="toast" :class="{ on: toast }" role="status" aria-live="polite">
                <template v-if="toast">
                    {{ toast.text }}<button v-if="toast.undo" type="button" @click="toast.undo">Undo</button>
                </template>
            </div>

            <footer class="foot">
                <button v-if="warning" type="button" class="warn" @click="guard(api.openSettings)">
                    <Icon name="warn" />{{ warning }}<span class="more">Details</span>
                </button>
                <div class="bar">
                    <span>{{ scanLine }}</span>
                    <span class="sp"></span>
                    <button type="button" class="act" @click="guard(api.openSettings)"><Icon name="gear" />Settings</button>
                    <button type="button" class="act" @click="guard(api.quit)"><Icon name="power" />Quit</button>
                </div>
            </footer>
        </template>
    </div>
</template>
