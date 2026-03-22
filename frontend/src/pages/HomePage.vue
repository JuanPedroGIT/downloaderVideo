<template>
  <div class="bg-gradient" aria-hidden="true" />

  <main class="home">
    <header class="home__header">
      <div class="home__logo">⬇</div>
      <h1 class="home__title">Media Tools</h1>
      <p class="home__subtitle">Everything you need in one place</p>
    </header>

    <div class="tool-grid">
      <!-- Video Downloader -->
      <RouterLink to="/video" class="tool-card">
        <div class="tool-card__icon">🎬</div>
        <div class="tool-card__body">
          <h2 class="tool-card__title">Video Downloader</h2>
          <p class="tool-card__desc">
            Download YouTube videos and audio in MP4, MP3, WebM and more formats.
          </p>
          <ul class="tool-card__features">
            <li>MP4, MP3, WebM, Audio, Video</li>
            <li>Async queue with live progress</li>
            <li>Playlist support</li>
          </ul>
        </div>
        <span class="tool-card__arrow">→</span>
      </RouterLink>

      <!-- Document Tools -->
      <RouterLink to="/docs" class="tool-card">
        <div class="tool-card__icon">📄</div>
        <div class="tool-card__body">
          <h2 class="tool-card__title">Document Tools</h2>
          <p class="tool-card__desc">
            Convert Word documents to PDF and merge multiple PDFs into one file.
          </p>
          <ul class="tool-card__features">
            <li>DOCX → PDF conversion</li>
            <li>Merge multiple PDFs</li>
            <li>Drag &amp; drop, custom order</li>
          </ul>
        </div>
        <span class="tool-card__arrow">→</span>
      </RouterLink>
    </div>
  </main>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>

<style scoped>
.home {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2.5rem;
  width: 100%;
  max-width: 720px;
  padding: 2rem 1rem;
  margin: 0 auto;
}

.home__header {
  text-align: center;
}

.home__logo {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}

.home__title {
  font-size: 2.2rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--grad-start), var(--grad-mid), var(--grad-end));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.4rem;
}

.home__subtitle {
  color: var(--text-secondary);
  font-size: 1rem;
}

/* Tool grid */
.tool-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  width: 100%;
}

@media (min-width: 560px) {
  .tool-grid { grid-template-columns: 1fr 1fr; }
}

.tool-card {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1.5rem;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  text-decoration: none;
  color: var(--text-primary);
  transition: border-color 0.2s, background 0.2s, transform 0.15s;
  position: relative;
}

.tool-card:hover {
  border-color: var(--grad-start);
  background: var(--bg-card-hover);
  transform: translateY(-2px);
}

.tool-card__icon {
  font-size: 2.2rem;
}

.tool-card__title {
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 0.3rem;
}

.tool-card__desc {
  font-size: 0.85rem;
  color: var(--text-secondary);
  line-height: 1.5;
}

.tool-card__features {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-top: 0.5rem;
}

.tool-card__features li {
  font-size: 0.78rem;
  color: var(--text-muted);
  padding-left: 1rem;
  position: relative;
}

.tool-card__features li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: var(--grad-start);
}

.tool-card__arrow {
  position: absolute;
  top: 1.25rem;
  right: 1.25rem;
  font-size: 1.1rem;
  color: var(--text-muted);
  transition: color 0.2s, transform 0.2s;
}

.tool-card:hover .tool-card__arrow {
  color: var(--grad-start);
  transform: translateX(3px);
}
</style>
