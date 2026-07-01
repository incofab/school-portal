import React, { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/inertia-react';
import { InertiaProgress } from '@inertiajs/progress';
import {
  ChakraProvider,
  ColorModeScript,
  ThemeConfig,
  extendTheme,
} from '@chakra-ui/react';
import { ProSidebarProvider } from 'react-pro-sidebar';
import './style/app.css';
import { BrandColor, ThemeColors } from './util/color-util';
import { InstitutionGroup } from './types/models';

// Read properties from the global window object
const appProps: {
  institutionGroup: InstitutionGroup | null;
} = (window as any).AppProps;

const config: ThemeConfig = {
  initialColorMode: 'light',
  useSystemColorMode: false,
};

const theme = extendTheme({
  colors: {
    brand:
      ThemeColors[appProps.institutionGroup?.brand_color as BrandColor] ??
      ThemeColors.edumanager,
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

const AppMain = ({ App, props }: any) => {
  return (
    <StrictMode>
      <ColorModeScript initialColorMode={theme.config.initialColorMode} />
      <ChakraProvider theme={theme}>
        <ProSidebarProvider>
          <App {...props} />
        </ProSidebarProvider>
      </ChakraProvider>
    </StrictMode>
  );
};

createInertiaApp({
  resolve: (name) => resolvePageComponent(name),
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(<AppMain App={App} props={props} />);
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
