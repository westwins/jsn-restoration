import { defineConfig } from 'vite';

export default defineConfig({
  publicDir: false,
  build: {
    outDir: 'public',
    emptyOutDir: false,
    sourcemap: false,
    rollupOptions: {
      input: {
        scripts: 'assets/app/app.js',
        graph: 'assets/app/graph.js',
      },
      output: {
        entryFileNames: 'js/[name].js',
        assetFileNames: (assetInfo) =>
          assetInfo.names?.some((name) => name.endsWith('.css'))
            ? 'css/styles.css'
            : 'assets/[name]-[hash][extname]',
      },
    },
  },
});
