<script setup lang="ts">
import { ref, watch } from 'vue';
import { api } from './api';
import EntryStamp from './EntryStamp.vue';
import type { PreviewJob } from './types';

// The live stamp preview: how two stored jobs look for a country, before it is saved.
// The settings window and the first run use it.
const props = defineProps<{ country: string | null }>();

const jobs = ref<PreviewJob[] | null>(null);
let request = 0;

watch(
    () => props.country,
    async (country) => {
        const mine = ++request;
        jobs.value = null;

        if (country === null) {
            return;
        }

        try {
            const preview = await api.stampPreview(country);

            if (mine === request) {
                jobs.value = preview.jobs;
            }
        } catch (error) {
            console.error(error);
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="preview" aria-live="polite">
        <span v-if="country === null" class="lab">Choose a country to see how jobs look.</span>
        <template v-else-if="jobs">
            <span v-for="job in jobs" :key="job.id" class="pair">
                <EntryStamp :stamp="job.stamp" :job-id="job.id" :seen="false" />
                <span class="lab">{{ job.title }}, {{ job.company }}</span>
            </span>
            <span v-if="jobs.length === 0" class="lab">No jobs yet. The preview shows two jobs after the first scan.</span>
        </template>
    </div>
</template>
