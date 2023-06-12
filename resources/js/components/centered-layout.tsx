import React from 'react';
import { Div } from '@/components/semantic';
import { BoxProps, Divider } from '@chakra-ui/react';
import { PageTitle } from './page-header';

interface Props {
  title?: string;
}
export default function CenteredLayout({
  children,
  title,
  ...props
}: Props & BoxProps) {
  return (
    <Div bg={'blue.50'} py={12} minH={'100vh'} {...props}>
      <Div
        bg={'white'}
        p={6}
        mx={'auto'}
        w={'full'}
        maxW={'md'}
        shadow={'md'}
        rounded={'md'}
      >
        {title && (
          <>
            <PageTitle>{title}</PageTitle>
            <Divider mt={3} mb={5} />
          </>
        )}
        <Div>{children}</Div>
      </Div>
    </Div>
  );
}
