import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue2'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
      clientPort: 8080,
    },
    watch: {
      usePolling: true,
    },
  }
})
