import React from 'react';
import { Div } from '@/components/semantic';
import { BoxProps } from '@chakra-ui/react';

export default function CenteredBox({ children, ...props }: BoxProps) {
  return (
    <Div maxWidth={'600px'} mx={'auto'} {...props}>
      {children}
    </Div>
  );
}
