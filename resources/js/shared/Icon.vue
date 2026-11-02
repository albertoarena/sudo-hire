<script lang="ts">
// The inline SVG icons of DESIGN.md "Icons": a 16 by 16 view box, stroke in currentColor.
export type IconName =
    | 'ext'
    | 'save'
    | 'saved'
    | 'hide'
    | 'check'
    | 'close'
    | 'chev'
    | 'warn'
    | 'gear'
    | 'stamp'
    | 'pin'
    | 'search'
    | 'coin'
    | 'bell'
    | 'feed'
    | 'building'
    | 'lock'
    | 'help'
    | 'power';
</script>

<script setup lang="ts">
defineProps<{ name: IconName }>();
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
        <path v-else-if="name === 'close'" d="M4 4l8 8M12 4l-8 8" stroke-width="1.6" />
        <g v-else-if="name === 'stamp'" stroke-width="1.5">
            <rect x="2" y="3.5" width="12" height="9" rx="1.5" transform="rotate(-6 8 8)" />
            <path d="m5.3 8.4 1.8 1.6 3.6-3.6" />
        </g>
        <g v-else-if="name === 'pin'" stroke-width="1.4">
            <path d="M8 14.5s4.5-4.3 4.5-8A4.5 4.5 0 0 0 3.5 6.5c0 3.7 4.5 8 4.5 8z" />
            <circle cx="8" cy="6.5" r="1.6" />
        </g>
        <g v-else-if="name === 'search'" stroke-width="1.4">
            <circle cx="7" cy="7" r="4.5" />
            <path d="m10.5 10.5 3.5 3.5" />
        </g>
        <g v-else-if="name === 'coin'">
            <circle cx="8" cy="8" r="6" stroke-width="1.4" />
            <path d="M10 5.6A3 3 0 1 0 10 10.4M4.8 7.2h4M4.8 8.8h4" stroke-width="1.3" />
        </g>
        <path v-else-if="name === 'bell'" d="M4 11V7a4 4 0 0 1 8 0v4l1.2 1.5H2.8zM6.5 14h3" stroke-width="1.4" />
        <g v-else-if="name === 'feed'" stroke-width="1.4">
            <path d="M3 3.5a9.5 9.5 0 0 1 9.5 9.5M3 7.5A5.5 5.5 0 0 1 8.5 13" />
            <circle cx="3.8" cy="12.2" r="1.2" fill="currentColor" stroke="none" />
        </g>
        <path v-else-if="name === 'building'" d="M3 14V3h6v11M9 6.5h4V14M2 14h12M5 5.5h2M5 8h2M5 10.5h2M11 9h0M11 11.5h0" stroke-width="1.4" />
        <g v-else-if="name === 'lock'" stroke-width="1.4">
            <rect x="3" y="7" width="10" height="7" rx="1.5" />
            <path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" />
        </g>
        <g v-else-if="name === 'help'" stroke-width="1.4">
            <circle cx="8" cy="8" r="6" />
            <path d="M6.3 6.3a1.8 1.8 0 1 1 2.4 1.7c-.5.2-.7.6-.7 1.1v.4M8 11.4v.1" />
        </g>
        <path v-else-if="name === 'power'" d="M8 2v6M4.6 4.2a5 5 0 1 0 6.8 0" stroke-width="1.5" />
    </svg>
</template>
