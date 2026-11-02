<script setup lang="ts">
import { computed, ref } from 'vue';
import CountryCombobox from '../../shared/CountryCombobox.vue';
import StampPreview from '../../shared/StampPreview.vue';
import CheckGroup from '../CheckGroup.vue';
import FieldError from '../FieldError.vue';
import { useStore } from '../store';
import type { Pane, Settings } from '../types';

defineEmits<{ 'open-pane': [pane: Pane] }>();

const store = useStore();
const settings = computed(() => store.settings.value!);

// The Mac gives the default. The list adds the stored zone in case the browser does not know it.
const zones = computed(() => {
    const known = typeof Intl.supportedValuesOf === 'function' ? Intl.supportedValuesOf('timeZone') : [];

    return known.includes(settings.value.timezone) ? known : [settings.value.timezone, ...known];
});

// Time difference: Off, 1 to 6 hours, or Custom (0 to 12) with a number field.
const PRESETS = [1, 2, 3, 4, 5, 6];
const stored = settings.value.timezoneOverlapHours;
const custom = ref(stored !== null && !PRESETS.includes(stored));
const hours = ref<string | number>(stored === null ? '' : String(stored));

const choice = computed(() => {
    const value = settings.value.timezoneOverlapHours;

    if (custom.value) {
        return 'custom';
    }

    return value === null ? '' : String(value);
});

function onChoice(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;
    custom.value = value === 'custom';

    if (!custom.value) {
        void store.save('timezoneOverlapHours', value === '' ? null : Number(value));
    }
}

function saveHours(): void {
    const value = String(hours.value).trim();
    void store.save('timezoneOverlapHours', value === '' ? null : Number(value));
}
</script>

<template>
    <h1>Where you work</h1>
    <p class="sub">Jobs must accept people in your country.</p>
    <div class="form">
        <label for="country">Country</label>
        <div class="ctl">
            <CountryCombobox
                id="country"
                :model-value="settings.country"
                :countries="store.countries.value"
                describedby="country-hint"
                @update:model-value="store.save('country', $event)"
            />
            <span id="country-hint" class="hint">Type to search all countries.</span>
            <FieldError id="country-error" :message="store.errors.country" />
        </div>

        <span class="lbl">Preview</span>
        <div class="ctl">
            <StampPreview :country="settings.country" />
            <span class="hint">How two real jobs look for the country you choose.</span>
        </div>

        <label for="tz">Timezone</label>
        <div class="ctl">
            <select id="tz" class="in" :value="settings.timezone" aria-describedby="tz-hint" @change="store.save('timezone', ($event.target as HTMLSelectElement).value)">
                <option v-for="zone in zones" :key="zone" :value="zone">{{ zone }}</option>
            </select>
            <span id="tz-hint" class="hint">Read from your Mac.</span>
            <FieldError id="timezone-error" :message="store.errors.timezone" />
        </div>

        <span class="lbl">Work mode</span>
        <CheckGroup
            field="workModes"
            label="Work mode"
            :options="[
                ['remote', 'Remote'],
                ['hybrid', 'Hybrid'],
                ['onsite', 'On-site'],
            ]"
        />

        <label for="unk">Location not stated</label>
        <div class="ctl">
            <select
                id="unk"
                class="in"
                :value="settings.unknownEligibility"
                aria-describedby="unk-hint"
                @change="store.save('unknownEligibility', ($event.target as HTMLSelectElement).value as Settings['unknownEligibility'])"
            >
                <option value="group">Show apart from matches</option>
                <option value="strict">Hide</option>
                <option value="lenient">Show with matches</option>
            </select>
            <span id="unk-hint" class="hint">Many jobs do not say where you can work from.</span>
            <FieldError id="unknownEligibility-error" :message="store.errors.unknownEligibility" />
        </div>

        <label for="ovl">Time difference</label>
        <div class="ctl">
            <div class="line">
                <select id="ovl" class="in" :value="choice" aria-describedby="ovl-hint" @change="onChoice">
                    <option value="">Off</option>
                    <option v-for="n in PRESETS" :key="n" :value="String(n)">Up to {{ n }} {{ n === 1 ? 'hour' : 'hours' }}</option>
                    <option value="custom">Custom…</option>
                </select>
                <span v-if="custom" class="line tight">
                    <input
                        id="ovl-n"
                        v-model="hours"
                        class="in num"
                        type="number"
                        min="0"
                        max="12"
                        aria-label="Hours"
                        @blur="saveHours"
                        @keydown.enter.prevent="saveHours"
                    />
                    hours
                </span>
            </div>
            <span id="ovl-hint" class="hint">
                {{
                    custom
                        ? 'From 0 to 12 hours. Example: Vancouver is 9 hours behind Rome.'
                        : 'Keeps jobs whose timezone is at most this far from yours. Jobs that do not state a timezone always show.'
                }}
            </span>
            <FieldError id="timezoneOverlapHours-error" :message="store.errors.timezoneOverlapHours" />
        </div>
    </div>
</template>
