import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

const vendorGroups = [
  {
    name: 'vendor-react',
    packages: ['react', 'react-dom', 'scheduler'],
  },
  {
    name: 'vendor-inertia',
    packages: ['@inertiajs', 'axios', 'ziggy-js'],
  },
  {
    name: 'vendor-chakra',
    packages: ['@chakra-ui', '@emotion', 'framer-motion', '@floating-ui'],
  },
  {
    name: 'vendor-editor',
    packages: ['@tinymce', 'tinymce'],
  },
  {
    name: 'vendor-charts',
    packages: ['chart.js', 'react-chartjs-2'],
  },
  {
    name: 'vendor-pdf',
    packages: [
      'jspdf',
      'html2canvas',
      'canvg',
      'rgbcolor',
      'stackblur-canvas',
      'svg-pathdata',
      'text-segmentation',
      'utrie',
    ],
  },
  {
    name: 'vendor-table',
    packages: ['jquery', 'datatables.net'],
  },
  {
    name: 'vendor-forms',
    packages: ['react-select'],
  },
  {
    name: 'vendor-utils',
    packages: ['lodash', 'date-fns'],
  },
  {
    name: 'vendor-layout',
    packages: ['react-pro-sidebar', '@heroicons'],
  },
  {
    name: 'vendor-sanitize',
    packages: ['dompurify'],
  },
  {
    name: 'vendor-upload',
    packages: ['react-dropzone', 'file-selector', 'attr-accept'],
  },
  {
    name: 'vendor-qr',
    packages: ['qrcode.react'],
  },
  {
    name: 'vendor-state',
    packages: ['immer'],
  },
];

function manualChunks(id) {
  if (!id.includes('node_modules')) {
    return;
  }

  const group = vendorGroups.find(({ packages }) =>
    packages.some((pkg) => id.includes(`/node_modules/${pkg}/`))
  );

  return group?.name ?? 'vendor-misc';
}

export default defineConfig({
  plugins: [
    react(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.tsx'],
      refresh: true,
    }),
  ],
  server: {
    hmr: {
      host: 'localhost',
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks,
      },
    },
  },
});
