<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import Icon from '../../shared/Icon.vue';
import FieldError from '../FieldError.vue';
import { useStore } from '../store';
import type { Pane } from '../types';

defineEmits<{ 'open-pane': [pane: Pane] }>();

const store = useStore();
const interval = ref(store.settings.value!.notificationIntervalHours);
const label = computed(() => `${interval.value} ${interval.value === 1 ? 'hour' : 'hours'}`);

watch(
    () => store.settings.value!.notificationIntervalHours,
    (hours) => (interval.value = hours),
);
</script>

<template>
    <h1>Notifications</h1>
    <p class="sub">One summary, only when there is something new.</p>
    <div class="form">
        <label for="every">Notify at most every</label>
        <div class="ctl">
            <div class="line">
                <input
                    id="every"
                    v-model.number="interval"
                    type="range"
                    min="1"
                    max="24"
                    :aria-valuetext="label"
                    @change="store.save('notificationIntervalHours', interval)"
                />
                <span aria-hidden="true">{{ label }}</span>
            </div>
            <FieldError id="notificationIntervalHours-error" :message="store.errors.notificationIntervalHours" />
        </div>

        <span class="lbl">Example</span>
        <div class="ctl">
            <div class="preview">
                <Icon name="stamp" class="sample-icon" />
                <span><b>Sudo Hire</b><br /><span class="lab">6 new jobs: 4 Laravel, 2 PHP</span></span>
            </div>
            <span class="hint">No notification when no new job matches.</span>
        </div>
    </div>
</template>
