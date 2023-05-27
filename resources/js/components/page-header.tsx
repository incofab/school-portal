import React from 'react';
import { Heading, HeadingProps } from '@chakra-ui/react';

export function PageTitle({ children, ...props }: HeadingProps) {
  return (
    <Heading size={'md'} fontWeight={'medium'} {...props}>
      {children}
    </Heading>
  );
}
