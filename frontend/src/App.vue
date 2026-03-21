<template>
  <!-- Animated background -->
  <div class="bg-gradient" aria-hidden="true" />

  <!-- Main card -->
  <main class="card" role="main">
    <!-- Header -->
    <div class="logo-row">
      <div class="logo-icon" aria-hidden="true">⬇</div>
      <div class="logo-text">
        <h1>YT Downloader</h1>
        <p>YouTube videos &amp; audio in any format</p>
      </div>
    </div>

    <hr class="divider" />

    <!-- URL Input -->
    <div class="field-group">
      <label class="field-label" for="url-input">YouTube URL</label>
      <input
        id="url-input"
        v-model="url"
        type="url"
        class="input-url"
        :class="{ invalid: urlError }"
        placeholder="https://www.youtube.com/watch?v=..."
        autocomplete="off"
        spellcheck="false"
        @blur="validateUrl(url)"
        @input="clearStatus"
      />
      <p v-if="urlError" class="field-error">{{ urlError }}</p>
    </div>

    <!-- Format Selector -->
    <FormatSelector v-model="selectedFormat" />

    <!-- Download Button -->
    <button
      id="download-btn"
      class="btn-download"
      :disabled="isLoading || !!urlError"
      @click="startDownload(url, selectedFormat)"
    >
      <span v-if="isLoading" class="spinner" aria-hidden="true" />
      <span v-else aria-hidden="true">⬇</span>
      <span>{{ isLoading ? (progress > 0 ? `Downloading ${progress}%` : 'Queuing...') : 'Download' }}</span>
    </button>

    <!-- Progress Bar -->
    <ProgressBar :visible="isLoading" :progress="progress" />

    <!-- Status Message -->
    <StatusMessage :message="status" :type="statusType" />

    <!-- Supported Platforms Note -->
    <p class="platforms-note">
      Currently supports <span>YouTube</span> · Vimeo &amp; TikTok coming soon
    </p>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { useDownload } from './composables/useDownload.js'
import FormatSelector from './components/FormatSelector.vue'
import ProgressBar    from './components/ProgressBar.vue'
import StatusMessage  from './components/StatusMessage.vue'

const url            = ref('')
const selectedFormat = ref('mp4')

const { isLoading, status, statusType, progress, urlError, startDownload, validateUrl, clearStatus } = useDownload()
</script>

<style scoped>
.field-error {
  font-size: 0.78rem;
  color: var(--error);
  margin-top: 0.35rem;
  padding-left: 0.2rem;
}
</style>
