<template>
  <div class="app-layout">
    <AppNavbar v-if="isAuthenticated" />
    <RouterView />
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
import AppNavbar from './components/AppNavbar.vue'
import { useAuth } from './composables/useAuth.js'

const { isAuthenticated } = useAuth()
</script>

<style>
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
</style>
