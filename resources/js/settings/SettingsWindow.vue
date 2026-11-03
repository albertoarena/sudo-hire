<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref } from 'vue';
import { api } from '../shared/api';
import Icon, { type IconName } from '../shared/Icon.vue';
import StoppedState from '../shared/StoppedState.vue';
import { failedSources } from '../shared/text';
import CompaniesPane from './panes/CompaniesPane.vue';
import JobsPane from './panes/JobsPane.vue';
import NotificationsPane from './panes/NotificationsPane.vue';
import PayPane from './panes/PayPane.vue';
import PrivacyPane from './panes/PrivacyPane.vue';
import SourcesPane from './panes/SourcesPane.vue';
import TroubleshootingPane from './panes/TroubleshootingPane.vue';
import WherePane from './panes/WherePane.vue';
import { createStore, STORE } from './store';
import type { Pane } from './types';

// The settings window (DESIGN.md "Settings window"): a sidebar of eight panes. Each control saves when it changes.
const store = createStore();
provide(STORE, store);

const PANES: { key: Pane; title: string; icon: IconName }[] = [
    { key: 'where', title: 'Where you work', icon: 'pin' },
    { key: 'jobs', title: 'Jobs', icon: 'search' },
    { key: 'pay', title: 'Pay', icon: 'coin' },
    { key: 'notifications', title: 'Notifications', icon: 'bell' },
    { key: 'sources', title: 'Sources', icon: 'feed' },
    { key: 'companies', title: 'Companies', icon: 'building' },
    { key: 'privacy', title: 'Privacy', icon: 'lock' },
    { key: 'troubleshooting', title: 'Troubleshooting', icon: 'help' },
];

const COMPONENTS = {
    where: WherePane,
    jobs: JobsPane,
    pay: PayPane,
    notifications: NotificationsPane,
    sources: SourcesPane,
    companies: CompaniesPane,
    privacy: PrivacyPane,
    troubleshooting: TroubleshootingPane,
};

const pane = ref<Pane>('where');
const failing = computed(() => failedSources(store.sources.value).length > 0);

// Electron ignores the page title, so PHP sets the window title too.
function select(key: Pane): void {
    pane.value = key;
    document.title = PANES.find((item) => item.key === key)!.title;
    void store.run(() => api.settingsTitle(key));
}

function load(): Promise<void> {
    return store.run(async () => {
        const [settings, countries, sources] = await Promise.all([api.settings(), api.countries(), api.sources()]);
        store.settings.value = settings;
        store.countries.value = countries;
        store.sources.value = sources;
    });
}

function onVisible(): void {
    if (document.visibilityState === 'visible') {
        void store.loadSources();
    }
}

// "Details" in the list opens the window on Sources with ?pane=sources.
function firstPane(): Pane {
    const asked = new URLSearchParams(window.location.search).get('pane');

    return PANES.find((item) => item.key === asked)?.key ?? 'where';
}

onMounted(async () => {
    await load();
    select(firstPane());
    window.addEventListener('focus', store.loadSources);
    document.addEventListener('visibilitychange', onVisible);
});

onBeforeUnmount(() => {
    window.removeEventListener('focus', store.loadSources);
    document.removeEventListener('visibilitychange', onVisible);
});
</script>

<template>
    <div v-if="store.stopped.value" class="settings-stopped"><StoppedState /></div>
    <div v-else class="settings">
        <nav class="side" aria-label="Settings">
            <button
                v-for="item in PANES"
                :key="item.key"
                type="button"
                :aria-current="pane === item.key ? 'page' : undefined"
                @click="select(item.key)"
            >
                <Icon :name="item.icon" />{{ item.title }}
                <span v-if="item.key === 'sources' && failing" class="bad" role="img" aria-label="A source has an error" title="A source has an error"></span>
            </button>
        </nav>
        <main class="pane">
            <component :is="COMPONENTS[pane]" v-if="store.settings.value" @open-pane="select" />
        </main>
    </div>
</template>
