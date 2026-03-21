<template>
  <div class="field-group">
    <label class="field-label">Output Format</label>
    <div class="format-grid" role="radiogroup" aria-label="Output format">
      <button
        v-for="fmt in formats"
        :key="fmt.value"
        type="button"
        class="format-btn"
        :class="{ active: modelValue === fmt.value }"
        :aria-pressed="modelValue === fmt.value"
        :title="fmt.description"
        @click="$emit('update:modelValue', fmt.value)"
      >
        <span class="format-icon" aria-hidden="true">{{ fmt.icon }}</span>
        <span>{{ fmt.label }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: String, required: true },
})

defineEmits(['update:modelValue'])

const formats = [
  { value: 'mp4',   label: 'MP4',   icon: '🎬', description: 'Video with audio (best quality)' },
  { value: 'mp3',   label: 'MP3',   icon: '🎵', description: 'Audio only – MP3' },
  { value: 'webm',  label: 'WebM',  icon: '🌐', description: 'Video in WebM format' },
  { value: 'audio', label: 'Audio', icon: '🎧', description: 'Best quality audio only' },
  { value: 'video', label: 'Video', icon: '📹', description: 'Video only (no audio)' },
]
</script>
