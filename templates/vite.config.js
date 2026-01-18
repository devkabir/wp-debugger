import { defineConfig } from 'vite'
import { resolve } from 'path'
import tailwindcss from '@tailwindcss/vite'
export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  build: {
    outDir: '../assets',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        'page': resolve(__dirname, 'src/page/index.js'),
      },
      output: {
        entryFileNames: () => {
          return 'js/[name].js'
        },
        assetFileNames: (assetInfo) => {
          if (assetInfo.names && assetInfo.names[0]) {
            const name = assetInfo.names[0]
            if (name.endsWith('.css')) {
              return 'css/[name][extname]'
            }
          }
          return '[name][extname]'
        }
      }
    }
  }
})
