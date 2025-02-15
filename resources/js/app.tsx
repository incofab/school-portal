import React, { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/inertia-react';
import { InertiaProgress } from '@inertiajs/progress';
import {
  ChakraProvider,
  ColorModeScript,
  Flex,
  Spinner,
  ThemeConfig,
  extendTheme,
} from '@chakra-ui/react';
import { ProSidebarProvider } from 'react-pro-sidebar';
import './style/app.css';

const config: ThemeConfig = {
  initialColorMode: 'dark',
  useSystemColorMode: false,
};

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
    },
  },
  config,
  components: {
    Select: {
      baseStyle: {
        control: {
          backgroundColor: 'gray.700', // Background color for the dropdown control
          borderColor: 'gray.600', // Border color for the dropdown control
        },
      },
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

const AppMain = ({ el, App, props }: any) => {
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    setIsLoading(false); // Simulate loading time
    // const timer = setTimeout(() => {
    //   setIsLoading(false); // Simulate loading time
    // }, 2000);
    // return () => clearTimeout(timer); // Clean up timer
  }, []);

  return (
    <StrictMode>
      <ColorModeScript initialColorMode={theme.config.initialColorMode} />
      <ChakraProvider theme={theme}>
        <ProSidebarProvider>
          {isLoading ? (
            <Flex align="center" justify="center" h="100vh">
              <Spinner size="xl" color="blue.500" />
            </Flex>
          ) : (
            <App {...props} />
          )}
        </ProSidebarProvider>
      </ChakraProvider>
    </StrictMode>
  );
};

createInertiaApp({
  resolve: (name) => resolvePageComponent(name),
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(<AppMain App={App} el={el} props={props} />);
    /*
    root.render(
      <StrictMode>
        <ColorModeScript initialColorMode={theme.config.initialColorMode} />
        <ChakraProvider theme={theme}>
          <ProSidebarProvider>
            <App {...props} />
          </ProSidebarProvider>
        </ChakraProvider>
      </StrictMode>
    );
    */
  },
});

InertiaProgress.init({ color: '#fff' });
