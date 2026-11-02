<script setup lang="ts">
// The inline SVG icons of DESIGN.md "Icons": a 16 by 16 view box, stroke in currentColor.
defineProps<{ name: 'ext' | 'save' | 'saved' | 'hide' | 'check' | 'chev' | 'warn' | 'gear' | 'power' }>();
</script>

<template>
    <svg viewBox="0 0 16 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
        <path v-if="name === 'ext'" d="M6 3H3.5A1.5 1.5 0 0 0 2 4.5v8A1.5 1.5 0 0 0 3.5 14h8a1.5 1.5 0 0 0 1.5-1.5V10M9 2h5v5M14 2 7.5 8.5" stroke-width="1.4" />
        <path v-else-if="name === 'save'" d="M4 2.5h8v11l-4-3-4 3z" stroke-width="1.4" />
        <path v-else-if="name === 'saved'" d="M4 2.5h8v11l-4-3-4 3z" fill="currentColor" stroke-width="1.4" />
        <g v-else-if="name === 'hide'" stroke-width="1.4">
            <path d="M2 8s2.2-4 6-4 6 4 6 4-2.2 4-6 4-6-4-6-4zM3 13 13 3" />
            <circle cx="8" cy="8" r="1.8" />
        </g>
        <path v-else-if="name === 'check'" d="m3 8.5 3.2 3L13 4.5" stroke-width="1.6" />
        <path v-else-if="name === 'chev'" d="m6 3.5 4.5 4.5L6 12.5" stroke-width="1.5" />
        <g v-else-if="name === 'warn'">
            <path d="M8 2.2 14.5 13.5h-13z" stroke-width="1.4" />
            <path d="M8 6.5v3.2M8 11.6v.1" stroke-width="1.6" />
        </g>
        <g v-else-if="name === 'gear'" stroke-width="1.4">
            <circle cx="8" cy="8" r="2.2" />
            <path d="M8 1.5v2M8 12.5v2M1.5 8h2M12.5 8h2M3.4 3.4l1.4 1.4M11.2 11.2l1.4 1.4M3.4 12.6l1.4-1.4M11.2 4.8l1.4-1.4" />
        </g>
        <path v-else-if="name === 'power'" d="M8 2v6M4.6 4.2a5 5 0 1 0 6.8 0" stroke-width="1.5" />
    </svg>
</template>
