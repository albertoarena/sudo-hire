<script setup lang="ts">
// The API message under a field after a 422. The control points to it with aria-describedby.
defineProps<{ id: string; message: string | undefined }>();
</script>

<template>
    <span v-if="message" :id="id" class="err" role="alert">{{ message }}</span>
</template>
