<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { api } from '../../shared/api';
import FieldError from '../FieldError.vue';
import { type SettingKey, useStore } from '../store';
import type { Pane, Rates } from '../types';

defineEmits<{ 'open-pane': [pane: Pane] }>();

const store = useStore();
const settings = computed(() => store.settings.value!);
const rates = ref<Rates | null>(null);

// Amount fields keep what the user types until blur or Enter. An empty field turns the minimum off.
const minYear = ref<string | number>(settings.value.minAnnualSalary === null ? '' : String(settings.value.minAnnualSalary));
const minDay = ref<string | number>(settings.value.minDayRate === null ? '' : String(settings.value.minDayRate));

function saveAmount(field: SettingKey, text: string | number): void {
    const value = String(text).trim();
    void store.save(field, value === '' ? null : Number(value));
}

const rateLine = computed(() => {
    if (!rates.value?.date) {
        return 'Rates: no exchange rates yet. Sudo Hire reads the European Central Bank reference rates at the next scan.';
    }

    const date = new Date(`${rates.value.date}T12:00:00Z`).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });

    return `Rates: ${rates.value.source} reference rates of ${date}, checked once a day. Amounts marked ≈ are converted.`;
});

onMounted(() =>
    store.run(async () => {
        rates.value = await api.rates();
    }),
);
</script>

<template>
    <h1>Pay</h1>
    <p class="sub">Filters compare converted amounts. The job always shows the original too.</p>
    <div class="form">
        <label for="cur">Show amounts in</label>
        <div class="ctl">
            <select id="cur" class="in" :value="settings.displayCurrency" @change="store.save('displayCurrency', ($event.target as HTMLSelectElement).value)">
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
                <option value="GBP">GBP</option>
            </select>
            <FieldError id="displayCurrency-error" :message="store.errors.displayCurrency" />
        </div>

        <label for="minyr">Minimum per year</label>
        <div class="ctl">
            <input
                id="minyr"
                v-model="minYear"
                class="in amount"
                type="number"
                min="1"
                placeholder="Off"
                aria-describedby="minyr-hint"
                @blur="saveAmount('minAnnualSalary', minYear)"
                @keydown.enter.prevent="saveAmount('minAnnualSalary', minYear)"
            />
            <span id="minyr-hint" class="hint">For employee jobs, in {{ settings.displayCurrency }}. Leave it empty to show every salary.</span>
            <FieldError id="minAnnualSalary-error" :message="store.errors.minAnnualSalary" />
        </div>

        <label for="payments">Payments per year</label>
        <div class="ctl">
            <select
                id="payments"
                class="in"
                :value="String(settings.monthlyPayments)"
                aria-describedby="payments-hint"
                @change="store.save('monthlyPayments', Number(($event.target as HTMLSelectElement).value))"
            >
                <option value="12">12</option>
                <option value="13">13</option>
                <option value="14">14</option>
            </select>
            <span id="payments-hint" class="hint">Monthly salaries are multiplied by this. Italian offers often quote 13 or 14.</span>
            <FieldError id="monthlyPayments-error" :message="store.errors.monthlyPayments" />
        </div>

        <label for="minday">Minimum per day</label>
        <div class="ctl">
            <input
                id="minday"
                v-model="minDay"
                class="in amount"
                type="number"
                min="1"
                placeholder="Off"
                aria-describedby="minday-hint"
                @blur="saveAmount('minDayRate', minDay)"
                @keydown.enter.prevent="saveAmount('minDayRate', minDay)"
            />
            <span id="minday-hint" class="hint">For contract jobs. Hourly rates count {{ settings.hoursPerDay }} hours a day.</span>
            <FieldError id="minDayRate-error" :message="store.errors.minDayRate" />
        </div>

        <label for="unks">Salary not stated</label>
        <div class="ctl">
            <select
                id="unks"
                class="in"
                :value="settings.unknownSalary"
                @change="store.save('unknownSalary', ($event.target as HTMLSelectElement).value as 'strict' | 'lenient')"
            >
                <option value="lenient">Show with "?"</option>
                <option value="strict">Hide</option>
            </select>
            <FieldError id="unknownSalary-error" :message="store.errors.unknownSalary" />
        </div>
    </div>
    <div class="note">{{ rateLine }}</div>
</template>
