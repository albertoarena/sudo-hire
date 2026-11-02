<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { api } from '../../shared/api';
import Icon from '../../shared/Icon.vue';
import { HELP_URL, ISSUE_URL } from '../../shared/links';
import { clock, errorText, failedSources } from '../../shared/text';
import { useStore } from '../store';
import type { Pane } from '../types';

const emit = defineEmits<{ 'open-pane': [pane: Pane] }>();

const store = useStore();
const copied = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

// The status line: the first failing source, or that all sources are fine.
const status = computed(() => {
    const failed = failedSources(store.sources.value);

    if (failed.length === 0) {
        return { ok: true, text: 'All sources are fine.' };
    }

    const first = failed[0];
    const when = first.lastRunAt ? ` at ${clock(first.lastRunAt)}` : '';
    const others = failed.length === 1 ? 'Other sources are fine.' : `${failed.length - 1} more ${failed.length === 2 ? 'source has' : 'sources have'} an error.`;

    return { ok: false, text: `${first.name}: ${errorText(first.lastError!.kind)}${when}. ${others}` };
});

const copy = () =>
    store.run(async () => {
        await api.copyDiagnostics();
        copied.value = true;
        clearTimeout(timer);
        timer = setTimeout(() => (copied.value = false), 1600);
    });

const open = (url: string) => store.run(() => api.openUrl(url));
const openLogFolder = () => store.run(api.openLogFolder);

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <h1>Troubleshooting</h1>
    <p class="sub">Most problems come from one source, or from a filter that is too strict.</p>
    <div class="note status-note">
        <span class="status" :class="{ err: !status.ok }"><i></i></span>
        <span>{{ status.text }}</span>
        <button type="button" class="act" @click="emit('open-pane', 'sources')">Show sources</button>
    </div>
    <div>
        <details class="faq">
            <summary>No new jobs for days</summary>
            <p>
                Check Posted within and your keywords in Jobs. In Where you work, a short time difference or "Hide" for Location not stated also
                removes many jobs.
            </p>
        </details>
        <details class="faq">
            <summary>A source shows "Certificate error"</summary>
            <p>The secure connection to the source failed. A network filter or an old certificate list can cause it. Update Sudo Hire. If it continues, report the problem.</p>
        </details>
        <details class="faq">
            <summary>No notifications</summary>
            <p>Open System Settings, Notifications, Sudo Hire, and allow notifications. Sudo Hire only notifies when new jobs match.</p>
        </details>
        <details class="faq">
            <summary>"Stopped in the background"</summary>
            <p>The part of the app that reads sources stopped. Choose Quit Sudo Hire in the menu bar list, then open the app again. Your jobs and settings are safe.</p>
        </details>
        <p class="more"><button type="button" class="act flush" @click="open(HELP_URL)">More answers on the help page<Icon name="ext" /></button></p>
    </div>
    <div class="btnrow">
        <button type="button" class="btn" @click="openLogFolder">Open log folder</button>
        <button type="button" class="btn" aria-live="polite" @click="copy">{{ copied ? 'Copied' : 'Copy diagnostic report' }}</button>
        <button type="button" class="btn primary" @click="open(ISSUE_URL)">Report a problem<Icon name="ext" /></button>
    </div>
    <span class="hint">The report has the app version, the macOS version and the status of each source. It has no settings, keywords or jobs.</span>
</template>
