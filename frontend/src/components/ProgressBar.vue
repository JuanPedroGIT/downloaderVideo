<template>
  <div v-if="visible" class="progress-container" aria-hidden="true">
    <div class="progress-inner" :style="{ width: progress + '%' }" />
  </div>
</template>

<script setup>
defineProps({
  visible:  { type: Boolean, required: true },
  progress: { type: Number, default: 0 },
})
</script>
