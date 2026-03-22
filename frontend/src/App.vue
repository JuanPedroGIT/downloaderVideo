<template>
  <!-- Animated background -->
  <div class="bg-gradient" aria-hidden="true" />

  <main class="card" role="main">
    <!-- Header -->
    <div class="logo-row">
      <div class="logo-icon" aria-hidden="true">⬇</div>
      <div class="logo-text">
        <h1>YT Downloader</h1>
        <p>YouTube videos, audio &amp; document tools</p>
      </div>
    </div>

    <hr class="divider" />

    <!-- Tabs -->
    <div class="tabs" role="tablist">
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'video' }"
        role="tab"
        :aria-selected="activeTab === 'video'"
        @click="activeTab = 'video'"
      >
        🎬 Video
      </button>
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'docs' }"
        role="tab"
        :aria-selected="activeTab === 'docs'"
        @click="activeTab = 'docs'"
      >
        📄 Documents
      </button>
    </div>

    <!-- Tab: Video Downloader -->
    <div v-if="activeTab === 'video'" role="tabpanel">
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

      <FormatSelector v-model="selectedFormat" />

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

      <ProgressBar :visible="isLoading" :progress="progress" />
      <StatusMessage :message="status" :type="statusType" />

      <p class="platforms-note">
        Currently supports <span>YouTube</span> · Vimeo &amp; TikTok coming soon
      </p>
    </div>

    <!-- Tab: Document Tools -->
    <div v-if="activeTab === 'docs'" role="tabpanel">
      <DocumentManager />
    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { useDownload }    from './composables/useDownload.js'
import FormatSelector     from './components/FormatSelector.vue'
import ProgressBar        from './components/ProgressBar.vue'
import StatusMessage      from './components/StatusMessage.vue'
import DocumentManager    from './components/DocumentManager.vue'

const activeTab      = ref('video')
const url            = ref('')
const selectedFormat = ref('mp4')

const { isLoading, status, statusType, progress, urlError, startDownload, validateUrl, clearStatus } = useDownload()
</script>

<style scoped>
.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}

.tab-btn {
  flex: 1;
  padding: 0.6rem 0.5rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: transparent;
  color: rgba(255,255,255,0.5);
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: rgba(255,255,255,0.05);
  color: var(--text-primary);
}

.tab-btn.active {
  background: linear-gradient(135deg, var(--grad-start), var(--grad-mid));
  border-color: transparent;
  color: #fff;
}

.field-error {
  font-size: 0.78rem;
  color: var(--error);
  margin-top: 0.35rem;
  padding-left: 0.2rem;
}
</style>
