import React from 'react';
import { Div } from '@/components/semantic';
import { BoxProps, Divider, useColorModeValue } from '@chakra-ui/react';
import { PageTitle } from './page-header';

interface Props {
  bgImage?: string|undefined;
  title?: string;
  boxProps?: BoxProps;
}
export default function CenteredLayout({
  children,
  bgImage,
  title,
  boxProps,
  ...props
}: Props & BoxProps) {
  return (
    <Div
      py={12}
      minH={'100vh'}
      {...props} // Spread any additional props you want to pass to the Div
      style={
        bgImage
          ? {
              backgroundImage: `url(${bgImage})`, // Dynamically set the background image
              backgroundPosition: 'center',
              backgroundSize: 'cover',
              backgroundAttachment: 'fixed',
            }
          : {}
      }
      bg={bgImage ? undefined : useColorModeValue('blue.50', 'gray.900')} // Only set bg color if no bgImage
    >
      <Div
        bg={useColorModeValue('white', 'gray.800')}
        p={6}
        mx={'auto'}
        w={'full'}
        maxW={'md'}
        shadow={'md'}
        rounded={'md'}
        {...boxProps}
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
