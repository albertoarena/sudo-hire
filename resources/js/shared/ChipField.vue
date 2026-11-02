<script setup lang="ts">
import { ref } from 'vue';
import Icon from './Icon.vue';

// The chip field of DESIGN.md "Inputs and form controls". Enter adds a value, the close button removes it.
const props = withDefaults(defineProps<{ modelValue: string[]; label: string; placeholder?: string; lowercase?: boolean; describedby?: string }>(), {
    placeholder: '',
    lowercase: true,
    describedby: undefined,
});
const emit = defineEmits<{ 'update:modelValue': [values: string[]] }>();

const text = ref('');

function add(): void {
    const value = props.lowercase ? text.value.trim().toLowerCase() : text.value.trim();
    text.value = '';

    if (value === '' || props.modelValue.some((item) => item.toLowerCase() === value.toLowerCase())) {
        return;
    }

    emit('update:modelValue', [...props.modelValue, value]);
}

function remove(value: string): void {
    emit(
        'update:modelValue',
        props.modelValue.filter((item) => item !== value),
    );
}
</script>

<template>
    <div class="chips">
        <span v-for="item in modelValue" :key="item" class="chip">
            {{ item }}<button type="button" :aria-label="`Remove ${item}`" @click="remove(item)"><Icon name="close" /></button>
        </span>
        <input v-model="text" type="text" :aria-label="label" :placeholder="placeholder" :aria-describedby="describedby" @keydown.enter.prevent="add" />
    </div>
</template>
