import React, { PropsWithChildren, ReactNode } from 'react';
import { HStack, Text, Spacer } from '@chakra-ui/react';
import { Div } from '@/components/semantic';

interface Props {
  title: string | ReactNode;
  rightElement?: string | ReactNode;
}

export default function ExamLayout({
  title,
  rightElement,
  children,
}: Props & PropsWithChildren) {
  return (
    <Div background={'brand.50'} minH={'100vh'}>
      <HStack
        align={'stretch'}
        background={'brand.700'}
        color={'white'}
        shadow={'md'}
        py={'25px'}
        px={'20px'}
      >
        <Text fontWeight={'bold'} fontSize={'25px'}>
          {title}
        </Text>
        <Spacer />
        <Div>{rightElement}</Div>
      </HStack>
      <Div py={'20px'}>{children}</Div>
    </Div>
  );
}
