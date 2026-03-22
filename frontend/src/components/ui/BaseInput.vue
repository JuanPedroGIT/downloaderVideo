<template>
  <label class="base-input">
    <span v-if="label" class="base-input__label">{{ label }}</span>
    <input
      v-bind="$attrs"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :autocomplete="autocomplete"
      :minlength="minlength"
      :maxlength="maxlength"
      class="base-input__field"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <span v-if="error" class="base-input__error" role="alert">{{ error }}</span>
  </label>
</template>

<script setup>
defineProps({
  modelValue:   { type: String, default: '' },
  label:        { type: String, default: '' },
  type:         { type: String, default: 'text' },
  placeholder:  { type: String, default: '' },
  error:        { type: String, default: '' },
  required:     { type: Boolean, default: false },
  autocomplete: { type: String, default: 'off' },
  minlength:    { type: [Number, String], default: undefined },
  maxlength:    { type: [Number, String], default: undefined },
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.base-input {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.base-input__label {
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-secondary);
}

.base-input__field {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 0.55rem 0.75rem;
  color: var(--text-primary);
  font-size: 0.9rem;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
  width: 100%;
}

.base-input__field:focus {
  outline: none;
  border-color: var(--grad-start);
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.18);
}

.base-input__error {
  font-size: 0.78rem;
  color: var(--error);
}
</style>
