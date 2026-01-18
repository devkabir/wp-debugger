import { defineConfig } from 'vite'
import { resolve } from 'path'
import tailwindcss from '@tailwindcss/vite'
export default defineConfig({
  plugins: [
    tailwindcss({
      optimize: true
    }),
  ],
  build: {
    outDir: '../dist',
    emptyOutDir: true,
    manifest: 'manifest.json',
    rollupOptions: {
      input: {
        'page': resolve(__dirname, 'src/page/index.js'),
        'dump': resolve(__dirname, 'src/dump/index.js'),
        'prism': resolve(__dirname, 'src/prism/index.js')
      }
    }
  }
})
