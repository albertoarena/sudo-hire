<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { api, ApiError } from '../../shared/api';
import { clock, errorDetails } from '../../shared/text';
import FieldError from '../FieldError.vue';
import { useStore } from '../store';
import type { Company, Pane } from '../types';

defineEmits<{ 'open-pane': [pane: Pane] }>();

const store = useStore();
const companies = ref<Company[]>([]);
const provider = ref('greenhouse');
const slug = ref('');
const error = ref<string | undefined>();

const PROVIDERS: Record<string, string> = { greenhouse: 'Greenhouse', lever: 'Lever', ashby: 'Ashby', personio: 'Personio' };

const load = () =>
    store.run(async () => {
        companies.value = await api.companies();
    });

const add = () =>
    store.run(async () => {
        try {
            await api.addCompany(provider.value, slug.value.trim());
        } catch (refused) {
            if (refused instanceof ApiError && refused.status === 422) {
                error.value = refused.message;

                return;
            }

            throw refused;
        }

        error.value = undefined;
        slug.value = '';
        await load();
    });

const remove = (company: Company) =>
    store.run(async () => {
        await api.removeCompany(company.id);
        await load();
    });

onMounted(load);
</script>

<template>
    <h1>Companies</h1>
    <p class="sub">Career pages that Sudo Hire reads directly. Most good jobs are here, not on boards.</p>
    <div class="tablewrap">
        <table class="src">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Career page</th>
                    <th>Last read</th>
                    <th><span class="sr">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="company in companies" :key="company.id">
                    <td>{{ company.name ?? company.slug }}</td>
                    <td>{{ PROVIDERS[company.provider] ?? company.provider }} <span class="mono">{{ company.slug }}</span></td>
                    <td>
                        <span v-if="company.lastError" class="status err"><i></i>{{ errorDetails(company.lastError.kind)[0] }}</span>
                        <span v-else-if="company.lastCheckedAt" class="status"><i></i>{{ clock(company.lastCheckedAt) }}</span>
                        <span v-else class="hint">Next scan</span>
                    </td>
                    <td class="end">
                        <button type="button" class="act" :aria-label="`Remove ${company.name ?? company.slug}`" @click="remove(company)">Remove</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <form class="ctl" @submit.prevent="add">
        <div class="line">
            <select id="provider" v-model="provider" class="in" aria-label="Career page provider">
                <option v-for="(name, key) in PROVIDERS" :key="key" :value="key">{{ name }}</option>
            </select>
            <input
                id="slug"
                v-model="slug"
                class="in grow"
                type="text"
                placeholder="Company name in the link, e.g. gitlab"
                aria-label="Company name in the link"
                :aria-describedby="error ? 'slug-error' : undefined"
                spellcheck="false"
            />
            <button class="btn" type="submit">Add</button>
        </div>
        <FieldError id="slug-error" :message="error" />
    </form>
</template>
