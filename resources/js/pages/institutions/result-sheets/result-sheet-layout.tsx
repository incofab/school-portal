import React, { PropsWithChildren } from 'react';
import { useColorMode } from '@chakra-ui/react';
import { Div } from '@/components/semantic';

export default function ResultSheetLayout({ children }: PropsWithChildren) {
  const { colorMode, setColorMode } = useColorMode();
  if (colorMode !== 'light') {
    setColorMode('light');
  }
  return <Div>{children}</Div>;
}
