import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      '/download': {
        target: process.env.VITE_API_URL || 'http://backend:8080',
        changeOrigin: true,
      },
      '/health': {
        target: process.env.VITE_API_URL || 'http://backend:8080',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: false,
  },
  define: {
    __API_URL__: JSON.stringify(process.env.VITE_API_URL || ''),
  },
})
