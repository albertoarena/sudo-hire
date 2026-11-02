<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { api } from '../../shared/api';
import ChipField from '../../shared/ChipField.vue';
import CheckGroup from '../CheckGroup.vue';
import FieldError from '../FieldError.vue';
import { useStore } from '../store';
import type { HiddenJobs, Pane } from '../types';

defineEmits<{ 'open-pane': [pane: Pane] }>();

const store = useStore();
const settings = computed(() => store.settings.value!);

const POSTED: [number, string][] = [
    [24, '24 hours'],
    [72, '3 days'],
    [168, '7 days'],
    [336, '14 days'],
    [720, '30 days'],
];

const hidden = ref<HiddenJobs | null>(null);
const confirming = ref(false);

const loadHidden = () =>
    store.run(async () => {
        hidden.value = await api.hiddenJobs();
    });

// "Show again" presses Not for me again: the API clears the state and the tombstones.
const showAgain = (id: number) =>
    store.run(async () => {
        await api.setState(id, 'hidden');
        await loadHidden();
    });

const clearOlder = () =>
    store.run(async () => {
        await api.clearOlderHiddenJobs();
        confirming.value = false;
        await loadHidden();
    });

function day(iso: string | null): string {
    return iso ? new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
}

onMounted(loadHidden);
</script>

<template>
    <h1>Jobs</h1>
    <p class="sub">Keywords match the title and tags, as whole words.</p>
    <div class="form">
        <span class="lbl">Include</span>
        <div class="ctl">
            <ChipField
                :model-value="settings.keywordsInclude"
                label="Add a keyword"
                placeholder="Add a keyword"
                describedby="include-hint"
                @update:model-value="store.save('keywordsInclude', $event)"
            />
            <span id="include-hint" class="hint">The notification groups jobs by these, in this order: "6 new jobs: 4 Laravel, 2 PHP".</span>
            <FieldError id="keywordsInclude-error" :message="store.errors.keywordsInclude" />
        </div>

        <span class="lbl">Exclude</span>
        <div class="ctl">
            <ChipField
                :model-value="settings.keywordsExclude"
                label="Add a keyword to exclude"
                placeholder="Add a keyword"
                @update:model-value="store.save('keywordsExclude', $event)"
            />
            <FieldError id="keywordsExclude-error" :message="store.errors.keywordsExclude" />
        </div>

        <span class="lbl"></span>
        <div class="ctl">
            <label class="check">
                <input
                    type="checkbox"
                    :checked="settings.matchDescription"
                    @change="store.save('matchDescription', ($event.target as HTMLInputElement).checked)"
                />Also match the job description
            </label>
        </div>

        <span class="lbl">Contract</span>
        <CheckGroup
            field="contractTypes"
            label="Contract"
            :options="[
                ['full_time', 'Full time'],
                ['part_time', 'Part time'],
                ['contract', 'Contract'],
            ]"
        />

        <span class="lbl">Seniority</span>
        <CheckGroup
            field="seniorities"
            label="Seniority"
            :options="[
                ['junior', 'Junior'],
                ['mid', 'Mid'],
                ['senior', 'Senior'],
                ['lead', 'Lead'],
            ]"
        />

        <label for="posted">Posted within</label>
        <div class="ctl">
            <select
                id="posted"
                class="in"
                :value="String(settings.postedWithinHours)"
                aria-describedby="posted-hint"
                @change="store.save('postedWithinHours', Number(($event.target as HTMLSelectElement).value))"
            >
                <option v-for="[hours, text] in POSTED" :key="hours" :value="String(hours)">{{ text }}</option>
            </select>
            <span id="posted-hint" class="hint">Jobs are kept for 30 days.</span>
            <FieldError id="postedWithinHours-error" :message="store.errors.postedWithinHours" />
        </div>

        <span class="lbl">Hide companies</span>
        <div class="ctl">
            <ChipField
                :model-value="settings.companyBlocklist"
                label="Add a company to hide"
                placeholder="Add a company"
                :lowercase="false"
                @update:model-value="store.save('companyBlocklist', $event)"
            />
            <FieldError id="companyBlocklist-error" :message="store.errors.companyBlocklist" />
        </div>

        <span class="lbl">Hidden jobs</span>
        <div class="ctl hidden-jobs">
            <template v-if="hidden">
                <ul v-if="hidden.jobs.length" class="rows" aria-label="Hidden jobs">
                    <li v-for="job in hidden.jobs" :key="job.id">
                        <span class="what">
                            <span class="t">{{ job.title }}</span>
                            <span class="m">{{ job.company }}<span class="sep"> · </span>hidden {{ day(job.hiddenAt) }}</span>
                        </span>
                        <button type="button" class="act" @click="showAgain(job.id)">Show again</button>
                    </li>
                </ul>
                <span v-else class="hint">No hidden jobs.</span>

                <template v-if="hidden.olderCount > 0">
                    <div v-if="confirming" class="confirm" role="group" aria-labelledby="clear-q">
                        <span id="clear-q">Forget {{ hidden.olderCount }} older hidden {{ hidden.olderCount === 1 ? 'job' : 'jobs' }}? A source that still lists one can show it again.</span>
                        <span class="btnrow">
                            <button type="button" class="btn" @click="confirming = false">Cancel</button>
                            <button type="button" class="btn" @click="clearOlder">Clear</button>
                        </span>
                    </div>
                    <span v-else><button type="button" class="act flush" @click="confirming = true">Clear older hidden jobs ({{ hidden.olderCount }})</button></span>
                    <span class="hint">Older hidden jobs are no longer stored, but Sudo Hire keeps them out of the list.</span>
                </template>
            </template>
        </div>
    </div>
</template>
