import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/inertia-react';
import { InertiaProgress } from '@inertiajs/progress';
import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { ProSidebarProvider } from 'react-pro-sidebar';

const theme = extendTheme({
  colors: {
    brand: {
      main: '#2a8864',
      50: '#ecf9f4',
      100: '#c5eddd',
      200: '#9ee1c7',
      300: '#77d5b1',
      400: '#50c99a',
      500: '#36af81',
      600: '#2a8864',
      700: '#1e6148',
      800: '#123a2b',
      900: '#06130e',
      // main: '#5E35B1',
      // 50: '#EDE7F6',
      // 100: '#D1C4E9',
      // 200: '#B39DDB',
      // 300: '#9575CD',
      // 400: '#7E57C2',
      // 500: '#673AB7',
      // 600: '#5E35B1',
      // 700: '#512DA8',
      // 800: '#4527A0',
      // 900: '#311B92',
    },
  },
});

const pages = import.meta.glob('./pages/**/*.tsx');

function resolvePageComponent(name: string) {
  for (const path in pages) {
    const fileName = path.replace('./pages/', '');
    if (fileName === `${name.replace('.', '/')}.tsx`) {
      return typeof pages[path] === 'function' ? pages[path]() : pages[path];
    }
  }

  throw new Error(`Page not found: ${name}`);
}

createInertiaApp({
  resolve: (name) => resolvePageComponent(name),
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(
      <ChakraProvider theme={theme}>
        <ProSidebarProvider>
          <App {...props} />
        </ProSidebarProvider>
      </ChakraProvider>
    );
  },
});

InertiaProgress.init({ color: '#fff' });
