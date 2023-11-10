import React from 'react';
import { Div } from '@/components/semantic';
import {
  BoxProps,
  Divider,
  HStack,
  Heading,
  HeadingProps,
  Spacer,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import { PageTitle } from './page-header';

export const SlabBody = ({ children, ...props }: BoxProps) => (
  <Div {...props}>{children}</Div>
);

export const SlabFooter = ({ children, ...props }: BoxProps) => (
  <Div {...props}>{children}</Div>
);

interface SlabHeadingProps {
  title?: string;
  rightElement?: React.ReactNode;
}
export const SlabHeading = ({
  children,
  title,
  rightElement,
  ...props
}: SlabHeadingProps & HeadingProps) => (
  <Heading size={'md'} fontWeight={'medium'} {...props}>
    {children ? (
      children
    ) : (
      <HStack>
        <PageTitle>{title}</PageTitle>
        <Spacer />
        {rightElement}
      </HStack>
    )}
    <Divider mt={2} />
  </Heading>
);

export default function Slab({ children, ...props }: BoxProps) {
  return (
    <Div
      border={'solid'}
      borderWidth={1}
      borderColor={useColorModeValue('gray.200', 'transparent')}
      rounded={'lg'}
      px={6}
      py={4}
      background={useColorModeValue('white', 'gray.800')}
      boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
      w={'full'}
      {...props}
    >
      <VStack align={'stretch'} spacing={4}>
        {children}
      </VStack>
    </Div>
  );
}
