<script setup lang="ts">
import { api } from '../../shared/api';
import Icon from '../../shared/Icon.vue';
import { PRIVACY_URL } from '../../shared/links';
import { useStore } from '../store';
import type { Pane } from '../types';

defineEmits<{ 'open-pane': [pane: Pane] }>();

const store = useStore();

const showDataFolder = () => store.run(api.showDataFolder);
const readPolicy = () => store.run(() => api.openUrl(PRIVACY_URL));
</script>

<template>
    <h1>Privacy</h1>
    <p class="sub">Everything stays on your Mac. No account, no telemetry.</p>
    <div class="form">
        <span class="lbl">Sudo Hire contacts</span>
        <div class="ctl">
            <ul class="plain">
                <li>The job boards that you keep on, at most once an hour.</li>
                <li>The career pages of the companies that you add, at most every 6 hours.</li>
                <li>The European Central Bank, once a day, for exchange rates.</li>
            </ul>
            <span class="hint">Search words are fixed (php, laravel). Your keywords never leave your Mac.</span>
        </div>

        <span class="lbl">Stored on your Mac</span>
        <div class="ctl">
            <ul class="plain">
                <li>Jobs, for 30 days. Saved jobs stay until you remove them.</li>
                <li>Your settings and the companies that you add.</li>
                <li>Exchange rates and a log file with warnings and errors.</li>
            </ul>
        </div>

        <span class="lbl"></span>
        <div class="ctl btnrow">
            <button type="button" class="btn" @click="showDataFolder">Show data folder in Finder</button>
            <button type="button" class="btn" @click="readPolicy">Read the privacy policy<Icon name="ext" /></button>
        </div>
    </div>
</template>
