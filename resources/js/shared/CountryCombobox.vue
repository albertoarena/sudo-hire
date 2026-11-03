<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import type { Country } from './types';

// The country field of DESIGN.md "Country combobox". The settings window and the first run use it.
const props = defineProps<{ modelValue: string | null; countries: Country[]; id: string; describedby?: string; invalid?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [code: string] }>();

const LIMIT = 40;

const nameOf = (code: string | null) => props.countries.find((country) => country.code === code)?.name ?? '';

const text = ref(nameOf(props.modelValue));
const open = ref(false);
// Focus shows the whole table. Typing filters it.
const browsing = ref(false);
const active = ref(0);
const listId = `${props.id}-list`;
const list = ref<HTMLUListElement | null>(null);

const matches = computed(() => {
    if (browsing.value) {
        return props.countries;
    }

    const query = text.value.trim().toLowerCase();

    return props.countries
        .filter((country) => query === '' || country.name.toLowerCase().includes(query) || country.code.toLowerCase() === query)
        .slice(0, LIMIT);
});

const expanded = computed(() => open.value && matches.value.length > 0);

watch(
    () => [props.modelValue, props.countries] as const,
    () => {
        if (!open.value) {
            text.value = nameOf(props.modelValue);
        }
    },
);

function show(): void {
    open.value = true;
    browsing.value = false;
    active.value = 0;
}

// The full list, with the current country active and in view.
async function browse(): Promise<void> {
    open.value = true;
    browsing.value = true;
    active.value = Math.max(
        props.countries.findIndex((country) => country.code === props.modelValue),
        0,
    );
    await nextTick();
    list.value?.querySelector<HTMLElement>('[aria-selected="true"]')?.scrollIntoView?.({ block: 'nearest' });
}

function close(): void {
    open.value = false;
    browsing.value = false;
}

function pick(country: Country): void {
    text.value = country.name;
    close();
    emit('update:modelValue', country.code);
}

// The text stays selected, so typing replaces it and filters.
function onFocus(event: FocusEvent): void {
    (event.target as HTMLInputElement).select();
    void browse();
}

function onKeydown(event: KeyboardEvent): void {
    if (!expanded.value) {
        if (event.key === 'ArrowDown') {
            void browse();
            event.preventDefault();
        }

        return;
    }

    if (event.key === 'ArrowDown') {
        active.value = Math.min(active.value + 1, matches.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        active.value = Math.max(active.value - 1, 0);
    } else if (event.key === 'Enter') {
        pick(matches.value[active.value]);
    } else if (event.key === 'Escape') {
        close();
    } else {
        return;
    }

    event.preventDefault();
}

// Leaving the field restores the current country.
function onBlur(): void {
    close();
    text.value = nameOf(props.modelValue);
}
</script>

<template>
    <div class="combo">
        <input
            :id="id"
            v-model="text"
            class="in"
            type="text"
            role="combobox"
            autocomplete="off"
            spellcheck="false"
            aria-autocomplete="list"
            :aria-expanded="expanded"
            :aria-controls="listId"
            :aria-activedescendant="expanded ? `${listId}-${active}` : undefined"
            :aria-describedby="describedby"
            :aria-invalid="invalid ? 'true' : undefined"
            @focus="onFocus"
            @input="show"
            @keydown="onKeydown"
            @blur="onBlur"
        />
        <ul v-show="expanded" :id="listId" ref="list" role="listbox" :aria-label="'Countries'">
            <li
                v-for="(country, index) in matches"
                :id="`${listId}-${index}`"
                :key="country.code"
                role="option"
                :aria-selected="index === active"
                @mousedown.prevent="pick(country)"
                @mouseenter="active = index"
            >
                {{ country.name }}<span>{{ country.code }}</span>
            </li>
        </ul>
    </div>
</template>
