<template>
  <BaseModal :model-value="modelValue" :title="editing ? 'Edit QR Code' : 'New QR Code'" @update:model-value="$emit('update:modelValue', $event)">
    <BaseAlert :message="error" type="error" />

    <form class="qr-form" @submit.prevent="submit">
      <BaseInput
        v-if="!editing"
        v-model="local.id"
        label="ID (slug)"
        placeholder="e.g. foto, mesa5"
        :maxlength="20"
        required
      />
      <BaseInput
        v-model="local.targetUrl"
        label="Target URL"
        type="url"
        placeholder="https://…"
        required
      />
      <label v-if="editing" class="field">
        <span>Active</span>
        <select v-model="local.isActive">
          <option :value="true">Yes</option>
          <option :value="false">No</option>
        </select>
      </label>
    </form>

    <template #footer>
      <BaseButton variant="secondary" @click="$emit('update:modelValue', false)">Cancel</BaseButton>
      <BaseButton :loading="saving" @click="submit">Save</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseModal  from '../../ui/BaseModal.vue'
import BaseButton from '../../ui/BaseButton.vue'
import BaseInput  from '../../ui/BaseInput.vue'
import BaseAlert  from '../../ui/BaseAlert.vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  editing:    { type: Object, default: null }, // QR object when editing, null when creating
  saving:     { type: Boolean, default: false },
  error:      { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'save'])

const local = ref({ id: '', targetUrl: '', isActive: true })

watch(() => props.modelValue, (open) => {
  if (open && props.editing) {
    local.value = { id: props.editing.id, targetUrl: props.editing.targetUrl, isActive: props.editing.isActive }
  } else if (open) {
    local.value = { id: '', targetUrl: '', isActive: true }
  }
})

function submit() {
  emit('save', { ...local.value })
}
</script>

<style scoped>
.qr-form { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.35rem; font-size: 0.85rem; }
.field span { color: var(--text-secondary); font-weight: 500; }
.field select {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 0.55rem 0.75rem;
  color: var(--text-primary);
  font-family: inherit;
  font-size: 0.9rem;
}
.field select:focus { outline: none; border-color: var(--grad-start); }
</style>
