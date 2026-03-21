<template>
  <div
    v-if="message"
    class="status-bar"
    :class="type"
    role="alert"
    aria-live="polite"
  >
    <span class="status-icon" aria-hidden="true">{{ icon }}</span>
    <span>{{ message }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  message: { type: String, default: '' },
  type:    { type: String, default: '' },
})

const icon = computed(() => ({ success: '✅', error: '❌', loading: '⏳' }[props.type] ?? '')
)
</script>
