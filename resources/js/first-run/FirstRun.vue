<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FieldError from '../settings/FieldError.vue';
import { api, ApiError, ServerStopped } from '../shared/api';
import ChipField from '../shared/ChipField.vue';
import CountryCombobox from '../shared/CountryCombobox.vue';
import Icon from '../shared/Icon.vue';
import StampPreview from '../shared/StampPreview.vue';
import StoppedState from '../shared/StoppedState.vue';
import { errorText } from '../shared/text';
import type { Country } from '../shared/types';
import type { FirstScan } from './types';

// The first run (DESIGN.md "First run"): four screens. Nothing is stored until "Run first scan",
// which saves the country, keywords and work modes in one PUT, then starts the first scan.
const STEPS = 4;
const POLL_MS = 1000;
type WorkMode = 'remote' | 'hybrid' | 'onsite';

const MODES: [WorkMode, string][] = [
    ['remote', 'Remote'],
    ['hybrid', 'Hybrid'],
    ['onsite', 'On-site'],
];

const step = ref(0);
const heading = ref<HTMLElement | null>(null);
const stopped = ref(false);
const busy = ref(false);

const countries = ref<Country[]>([]);
const country = ref<string | null>(null);
const preset = ref<string | null>(null);
const keywords = ref<string[]>([]);
const workModes = ref<string[]>([]);
const countryError = ref<string | undefined>();
const saveError = ref<string | undefined>();
const scan = ref<FirstScan | null>(null);
const testing = ref(false);
const testText = ref('');
let timer: ReturnType<typeof setTimeout> | undefined;

const primaryText = computed(() => ['Start', 'Next', 'Run first scan', 'Open the list'][step.value]);

async function guard(work: () => Promise<void>): Promise<void> {
    try {
        await work();
    } catch (error) {
        if (error instanceof ServerStopped) {
            stopped.value = true;
        } else {
            console.error(error);
        }
    }
}

function setMode(mode: WorkMode, on: boolean): void {
    const next = on ? [...workModes.value, mode] : workModes.value.filter((item) => item !== mode);
    workModes.value = MODES.map(([value]) => value).filter((value) => next.includes(value));
}

function poll(): void {
    if (scan.value?.finished !== false) {
        return;
    }

    timer = setTimeout(
        () =>
            guard(async () => {
                scan.value = await api.firstScan();
                poll();
            }),
        POLL_MS,
    );
}

async function runFirstScan(): Promise<void> {
    try {
        await api.saveSettings({ country: country.value, keywordsInclude: keywords.value, workModes: workModes.value });
    } catch (error) {
        if (error instanceof ApiError && error.status === 422) {
            saveError.value = error.message;

            return;
        }

        throw error;
    }

    saveError.value = undefined;
    scan.value = await api.startFirstScan();
    step.value = 3;
    poll();
}

function primary(): Promise<void> {
    if (busy.value) {
        return Promise.resolve();
    }

    busy.value = true;

    return guard(async () => {
        if (step.value === 1 && country.value === null) {
            countryError.value = 'Choose the country you work from.';
            document.getElementById('fr-country')?.focus();
        } else if (step.value === 2) {
            await runFirstScan();
        } else if (step.value === 3) {
            await api.openList();
        } else {
            step.value++;
        }
    }).finally(() => {
        busy.value = false;
    });
}

// The first notification makes macOS ask if Sudo Hire can send notifications. Without a yes,
// the summaries never show, so the first run asks while the user is looking.
function sendTestNotification(): Promise<void> {
    testing.value = true;

    return guard(async () => {
        try {
            await api.testNotification();
            testText.value = 'Sent. If no notification shows, open System Settings, Notifications, Sudo Hire, and allow notifications.';
        } catch (error) {
            if (!(error instanceof ApiError)) {
                throw error;
            }

            testText.value = 'Sudo Hire could not send it. Open System Settings, Notifications, Sudo Hire, and allow notifications.';
        }
    }).finally(() => {
        testing.value = false;
    });
}

function pickCountry(code: string): void {
    country.value = code;
    countryError.value = undefined;
}

// Each new screen moves the focus to its heading, for screen readers.
watch(step, async () => {
    await nextTick();
    heading.value?.focus();
});

onMounted(() =>
    guard(async () => {
        const [settings, table, mac] = await Promise.all([api.settings(), api.countries(), api.countryPreset()]);
        countries.value = table;
        preset.value = mac.country;
        country.value = mac.country ?? settings.country;
        keywords.value = settings.keywordsInclude;
        workModes.value = settings.workModes;
    }),
);

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div v-if="stopped" class="settings-stopped"><StoppedState /></div>
    <div v-else class="fr">
        <main class="fr-body">
            <template v-if="step === 0">
                <h1 ref="heading" tabindex="-1">Jobs you can take, from where you live</h1>
                <p>
                    Sudo Hire reads company career pages and job boards, keeps the jobs that accept people in your country, and sends one summary every
                    few hours. Everything stays on your Mac.
                </p>
                <div class="cmdline">$ sudo hire --remote-from here</div>
            </template>

            <template v-else-if="step === 1">
                <h1 ref="heading" tabindex="-1">Where do you work from?</h1>
                <p v-if="preset">Each job shows if it accepts people in this country. It comes from your Mac; change it if it is wrong.</p>
                <p v-else>Each job shows if it accepts people in this country. Type to search all countries.</p>
                <div>
                    <label for="fr-country" class="sr">Country</label>
                    <CountryCombobox
                        id="fr-country"
                        :model-value="country"
                        :countries="countries"
                        :invalid="countryError !== undefined"
                        :describedby="countryError ? 'country-error' : undefined"
                        @update:model-value="pickCountry"
                    />
                    <FieldError id="country-error" :message="countryError" />
                </div>
                <StampPreview :country="country" />
            </template>

            <template v-else-if="step === 2">
                <h1 ref="heading" tabindex="-1">What do you look for?</h1>
                <p>Keywords match job titles. The first ones also name the groups in the notification.</p>
                <ChipField v-model="keywords" label="Add a keyword" placeholder="Add a keyword" />
                <div class="line" role="group" aria-label="Work mode">
                    <label v-for="[value, text] in MODES" :key="value" class="check">
                        <input type="checkbox" :checked="workModes.includes(value)" @change="setMode(value, ($event.target as HTMLInputElement).checked)" />{{ text }}
                    </label>
                </div>
                <p v-if="saveError" class="err" role="alert">{{ saveError }}</p>
            </template>

            <template v-else>
                <h1 ref="heading" tabindex="-1">First scan</h1>
                <p>Sudo Hire is reading the sources. This takes about a minute the first time.</p>
                <ul class="scanlist" aria-live="polite">
                    <li v-for="source in scan?.sources ?? []" :key="source.key" :class="{ ok: source.status === 'done', no: source.status === 'failed' }">
                        <Icon v-if="source.status === 'done'" name="check" />
                        <span v-else class="ico" aria-hidden="true"></span>
                        <span>{{ source.name }}</span>
                        <span class="state">{{ source.status === 'failed' ? errorText(source.errorKind ?? 'other') : source.status }}</span>
                    </li>
                </ul>
                <section class="fr-notify" aria-labelledby="fr-notify-title">
                    <h2 id="fr-notify-title">Notifications</h2>
                    <p>Sudo Hire sends one summary when new jobs match. macOS asks once if Sudo Hire can send notifications: choose Allow.</p>
                    <div><button type="button" class="btn" :disabled="testing" @click="sendTestNotification">Send a test notification</button></div>
                    <p class="hint" aria-live="polite">{{ testText }}</p>
                </section>
            </template>
        </main>

        <nav class="nav" aria-label="First run">
            <div class="steps" aria-hidden="true"><i v-for="n in STEPS" :key="n" :class="{ on: n <= step + 1 }"></i></div>
            <span class="sr">Step {{ step + 1 }} of {{ STEPS }}</span>
            <span class="sp"></span>
            <button v-show="step > 0" type="button" class="btn" @click="step--">Back</button>
            <button type="button" class="btn primary" :disabled="busy" @click="primary">{{ primaryText }}</button>
        </nav>
    </div>
</template>
