<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { clock, errorDetails } from '../../shared/text';
import type { Source } from '../../shared/types';
import FieldError from '../FieldError.vue';
import { useStore } from '../store';
import type { Pane } from '../types';

defineEmits<{ 'open-pane': [pane: Pane] }>();

const store = useStore();
const disabled = computed(() => store.settings.value!.disabledSources);

function every(source: Source): string {
    const hours = Math.round(source.minIntervalMinutes / 60);

    return `every ${hours} ${hours === 1 ? 'hour' : 'hours'}`;
}

async function toggle(event: Event, key: string): Promise<void> {
    const box = event.target as HTMLInputElement;
    const next = box.checked ? disabled.value.filter((item) => item !== key) : [...disabled.value, key];

    if (await store.save('disabledSources', next)) {
        await store.loadSources();
    } else {
        box.checked = !box.checked;
    }
}

onMounted(store.loadSources);
</script>

<template>
    <h1>Sources</h1>
    <p class="sub">Each source is read no more often than its terms allow.</p>
    <FieldError id="disabledSources-error" :message="store.errors.disabledSources" />
    <div class="tablewrap">
        <table class="src">
            <thead>
                <tr>
                    <th>On</th>
                    <th>Source</th>
                    <th>Last read</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="source in store.sources.value" :key="source.key">
                    <td><input type="checkbox" class="switch" :aria-label="source.name" :checked="!disabled.includes(source.key)" @change="toggle($event, source.key)" /></td>
                    <td>
                        {{ source.name }}<br />
                        <span class="hint">At most {{ every(source) }}</span>
                    </td>
                    <td>{{ source.lastRunAt ? clock(source.lastRunAt) : 'Not yet' }}</td>
                    <td>
                        <template v-if="source.lastError">
                            <span class="status err"><i></i>{{ errorDetails(source.lastError.kind)[0] }}</span><br />
                            <span class="why">{{ errorDetails(source.lastError.kind)[1] }}</span>
                        </template>
                        <span v-else-if="source.lastRunAt" class="status"><i></i>{{ source.jobCount }} {{ source.jobCount === 1 ? 'job' : 'jobs' }}</span>
                        <span v-else class="hint">Next scan</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
