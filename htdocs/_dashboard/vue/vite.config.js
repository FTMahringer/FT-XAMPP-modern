import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'node:path'

export default defineConfig({
  plugins: [vue()],
  root: '.',
  base: '/_dashboard/',
  build: {
    outDir:  process.env.OUT_DIR || 'dist',
    emptyOutDir: true
  },
  resolve: {
    alias: { '@': path.resolve(__dirname, 'src') }
  },
  server: {
    port: 5173,
    strictPort: true
  },
})