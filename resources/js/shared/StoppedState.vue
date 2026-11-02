<script setup lang="ts">
declare global {
    interface Window {
        Native?: { contextMenu: (template: { label?: string; role?: string }[]) => void };
    }
}

function quit(): void {
    // PHP is gone, so the API cannot quit. Electron's quit role still can.
    window.Native?.contextMenu([{ label: 'Quit Sudo Hire', role: 'quit' }]);
}
</script>

<template>
    <div class="stopped">
        <svg class="big" viewBox="0 0 16 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 2.2 14.5 13.5h-13z" stroke-width="1.4" />
            <path d="M8 6.5v3.2M8 11.6v.1" stroke-width="1.6" />
        </svg>
        <h1>Sudo Hire stopped in the background</h1>
        <p>Quit Sudo Hire and open it again. Your saved jobs are safe.</p>
        <button type="button" class="btn primary" @click="quit">Quit Sudo Hire</button>
    </div>
</template>
