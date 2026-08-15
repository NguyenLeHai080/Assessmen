import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: { port: 5173 },
  build: {
    outDir: '../plugin-assessment/assets/app',
    emptyOutDir: true,
    manifest: true,
  },
})
