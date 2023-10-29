import React, { PropsWithChildren, ReactNode } from 'react';
import { HStack, Text, Spacer, Icon } from '@chakra-ui/react';
import { Div } from '@/components/semantic';
import { HomeIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  title: string | ReactNode;
  rightElement?: string | ReactNode;
}

export default function ExamLayout({
  title,
  rightElement,
  children,
}: Props & PropsWithChildren) {
  const { instRoute } = useInstitutionRoute();
  const { currentInstitution } = useSharedProps();
  return (
    <Div background={'brand.50'} minH={'100vh'}>
      <HStack
        align={'stretch'}
        background={'brand.700'}
        color={'white'}
        shadow={'md'}
        py={'15px'}
        px={'20px'}
      >
        <HStack>
          <Text as={InertiaLink} href={instRoute('external.home')} px={3}>
            <Icon as={HomeIcon} fontSize={'4xl'} />
          </Text>
          <Div>
            <Text fontWeight={'bold'} fontSize={'18px'} color={'brand.100'}>
              {currentInstitution.name}
            </Text>
            <Text fontWeight={'bold'} fontSize={'18px'}>
              {title}
            </Text>
          </Div>
        </HStack>
        <Spacer />
        <Div>{rightElement}</Div>
      </HStack>
      <Div py={'20px'}>{children}</Div>
    </Div>
  );
}
