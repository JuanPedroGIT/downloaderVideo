<template>
  <div class="auth-wrap">
    <div class="auth-card">
      <div v-if="icon" class="auth-card__icon" aria-hidden="true">{{ icon }}</div>
      <h1 class="auth-card__title">{{ title }}</h1>
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon:  { type: String, default: '' },
  title: { type: String, required: true },
})
</script>

<style scoped>
.auth-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.auth-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 2rem;
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  text-align: center;
}

.auth-card__icon {
  font-size: 2.5rem;
}

.auth-card__title {
  font-size: 1.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, var(--grad-start), var(--grad-mid), var(--grad-end));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
</style>
