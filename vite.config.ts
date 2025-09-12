import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    }
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `
          @use "@/assets/styles/variables.scss" as variables;
          @use "@/assets/styles/mixins.scss" as mixins;
          @use "@/assets/styles/base.scss" as base-style;
        `
      }
    }
  },
  build: {
    outDir: 'dist', // Output directory for static files
    assetsDir: 'assets', // Static assets like images, SVGs
    sourcemap: false, // Disable sourcemaps for production
  },
  base: '/vue-ams/', // Adjust for subpath deployment, e.g., '/my-app/' for GitHub Pages
})