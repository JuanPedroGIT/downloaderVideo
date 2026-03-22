<template>
  <nav class="navbar">
    <RouterLink to="/" class="navbar__logo">Media Tools</RouterLink>
    <div class="navbar__right">
      <span class="navbar__user">{{ username }}</span>
      <button class="navbar__logout" @click="logout">Logout</button>
    </div>
  </nav>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

const { username, logout } = useAuth()
</script>

<style scoped>
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1.5rem;
  background: var(--bg-card);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 50;
}

.navbar__logo {
  font-weight: 800;
  font-size: 1rem;
  text-decoration: none;
  background: linear-gradient(135deg, var(--grad-start), var(--grad-end));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.navbar__right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.navbar__user {
  font-size: 0.82rem;
  color: var(--text-secondary);
}

.navbar__logout {
  background: transparent;
  border: 1px solid #f87171;
  color: #f87171;
  border-radius: var(--radius);
  padding: 0.35rem 0.85rem;
  font-size: 0.82rem;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.navbar__logout:hover { background: #f87171; color: #fff; }
</style>
