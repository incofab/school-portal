import React from 'react';
import { Div } from '@/components/semantic';
import {
  BoxProps,
  Divider,
  HStack,
  Spacer,
  Text,
  useColorModeValue,
} from '@chakra-ui/react';
import { PageTitle } from './page-header';

interface Props {
  bgImage?: string | undefined;
  title?: string;
  rightHeader?: React.ReactNode;
  boxProps?: BoxProps;
}
export default function CenteredLayout({
  children,
  bgImage,
  title,
  rightHeader,
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
              // backgroundImage: `url(${bgImage})`, // Dynamically set the background image
              backgroundPosition: 'center',
              backgroundSize: 'cover',
              backgroundAttachment: 'fixed',

              backgroundImage: `linear-gradient(
                  rgba(0, 0, 0, 0.6), 
                  rgba(0, 0, 0, 0.6)
                ), url(${bgImage})`,
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
            <PageTitle>
              <HStack>
                <Text as="span">{title}</Text>
                <Spacer />
                {rightHeader ?? ''}
              </HStack>
            </PageTitle>
            <Divider mt={3} mb={5} />
          </>
        )}
        <Div>{children}</Div>
      </Div>
    </Div>
  );
}
