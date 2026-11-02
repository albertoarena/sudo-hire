<script setup lang="ts">
import { computed } from 'vue';
import EntryStamp from './EntryStamp.vue';
import Icon from './Icon.vue';
import { age, terms } from './text';
import type { JobState, Row } from './types';

const props = defineProps<{ row: Row; group?: boolean }>();
const emit = defineEmits<{ act: [row: Row, state: JobState]; apply: [row: Row] }>();

const unseen = computed(() => !props.row.seen && !props.group);
const termText = computed(() => terms(props.row));
</script>

<template>
    <article class="job" :class="{ unseen }" :data-id="row.id">
        <span v-if="unseen" class="dot" aria-label="Unseen"></span>
        <div class="top">
            <div class="t">{{ row.title }}</div>
            <EntryStamp :stamp="row.stamp" :job-id="row.id" :seen="!unseen" />
        </div>
        <div class="meta">
            <span>{{ row.company }}</span>
            <span class="sep">·</span>
            <span>{{ row.attribution }}</span>
            <span v-for="name in row.alsoOn" :key="name" class="badge">also on {{ name }}</span>
            <span class="sep">·</span>
            <span>{{ age(row.postedAt) }}</span>
        </div>
        <template v-if="row.salary.original">
            <div class="pay">
                <span v-if="row.salary.unknown" class="q" title="The salary cannot be compared">?</span>
                <span>{{ row.salary.original }}</span>
                <span v-if="row.salary.converted" class="conv">{{ row.salary.converted }}</span>
                <span v-if="row.salary.ote" class="badge" title="On-target earnings">OTE</span>
            </div>
            <div v-if="termText" class="pay"><span class="terms">{{ termText }}</span></div>
        </template>
        <div v-else class="pay">
            <span class="q" title="Salary not stated">?</span>
            <span class="conv">Salary not stated</span>
            <template v-if="termText">
                <span class="sep">·</span>
                <span class="terms">{{ termText }}</span>
            </template>
        </div>
        <div class="acts">
            <button type="button" class="act" :aria-pressed="row.state === 'saved'" @click="emit('act', row, 'saved')">
                <Icon :name="row.state === 'saved' ? 'saved' : 'save'" />{{ row.state === 'saved' ? 'Saved' : 'Save' }}
            </button>
            <button v-if="row.state === null" type="button" class="act" @click="emit('act', row, 'hidden')"><Icon name="hide" />Not for me</button>
            <button v-if="!group" type="button" class="act" :aria-pressed="row.state === 'applied'" @click="emit('act', row, 'applied')">
                <Icon name="check" />Applied
            </button>
            <span class="sp"></span>
            <button type="button" class="btn" @click="emit('apply', row)">Apply <Icon name="ext" /></button>
        </div>
    </article>
</template>
