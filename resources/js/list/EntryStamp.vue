<script setup lang="ts">
import { computed } from 'vue';
import { stampLines, tilt } from './text';
import type { Stamp } from './types';

const props = defineProps<{ stamp: Stamp; jobId: number; seen: boolean }>();

const lines = computed(() => stampLines(props.stamp));
const kind = computed(() => ({ admitted: '', not_stated: 'unk', refused: 'no' })[props.stamp.kind]);
</script>

<template>
    <span class="stamp" :class="[kind, { seen }]" :style="{ '--r': tilt(jobId) }" :aria-label="`${lines[0]} ${lines[1]}`">
        <span class="c" aria-hidden="true">{{ lines[0] }}</span>
        <span class="w" aria-hidden="true">{{ lines[1] }}</span>
    </span>
</template>
