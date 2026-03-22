<template>
  <div class="bg-gradient" aria-hidden="true" />

  <main class="card" role="main">
    <div class="page-header">
      <RouterLink to="/" class="btn-back">← Back</RouterLink>
    </div>

    <div class="logo-row">
      <div class="logo-icon" aria-hidden="true">📄</div>
      <div class="logo-text">
        <h1>Document Tools</h1>
        <p>Convert DOCX to PDF &amp; merge multiple PDFs</p>
      </div>
    </div>

    <hr class="divider" />

    <DocumentManager />
  </main>
</template>

<script setup>
import { RouterLink }    from 'vue-router'
import DocumentManager   from '../components/DocumentManager.vue'
</script>

<style scoped>
.page-header {
  margin-bottom: 1rem;
}

.btn-back {
  font-size: 0.85rem;
  color: var(--text-secondary);
  text-decoration: none;
  transition: color 0.2s;
}

.btn-back:hover {
  color: var(--text-primary);
}
</style>
