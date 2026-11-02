<script setup lang="ts">
import FieldError from './FieldError.vue';
import { type SettingKey, useStore } from './store';

// A row of checkboxes for one list setting, for example the work modes.
const props = defineProps<{ field: SettingKey; options: [string, string][]; label: string }>();
const store = useStore();

const values = () => (store.settings.value?.[props.field] as string[] | undefined) ?? [];

async function toggle(event: Event, value: string): Promise<void> {
    const box = event.target as HTMLInputElement;
    const next = box.checked ? [...values(), value] : values().filter((item) => item !== value);

    if (!(await store.save(props.field, next))) {
        box.checked = !box.checked;
    }
}
</script>

<template>
    <div class="ctl">
        <div class="line" role="group" :aria-label="label" :aria-describedby="store.errors[field] ? `${field}-error` : undefined">
            <label v-for="[value, text] in options" :key="value" class="check">
                <input type="checkbox" :value="value" :checked="values().includes(value)" @change="toggle($event, value)" />{{ text }}
            </label>
        </div>
        <FieldError :id="`${field}-error`" :message="store.errors[field]" />
    </div>
</template>
