<template>
  <BaseModal :model-value="modelValue" :title="`QR — ${qr?.id}`" @update:model-value="$emit('update:modelValue', $event)">
    <div v-if="svg" class="qr-svg" v-html="svg" />
    <p v-if="qr?.targetUrl" class="qr-url">{{ qr.targetUrl }}</p>

    <template #footer>
      <BaseButton variant="secondary" @click="$emit('update:modelValue', false)">Close</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import BaseModal  from '../../ui/BaseModal.vue'
import BaseButton from '../../ui/BaseButton.vue'

defineProps({
  modelValue: { type: Boolean, required: true },
  svg:        { type: String, default: '' },
  qr:         { type: Object, default: null },
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.qr-svg { display: flex; justify-content: center; }
.qr-svg :deep(svg) { width: 240px; height: 240px; }
.qr-url { font-size: 0.75rem; color: var(--text-secondary); word-break: break-all; text-align: center; }
</style>
