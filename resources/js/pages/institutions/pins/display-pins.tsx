import React from 'react';
import { Pin } from '@/types/models';
import { Div } from '@/components/semantic';
import { Avatar, HStack, Text } from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';

interface Props {
  pins: Pin[];
  resultCheckerUrl: string;
}

export default function ListPrintedPins({ pins, resultCheckerUrl }: Props) {
  const { currentInstitution } = useSharedProps();
  return (
    <Div textAlign={'center'}>
      {pins.map((pin) => (
        <Div
          display={'inline-block'}
          width={'300px'}
          mx={1}
          my={1}
          border={'1px solid #000'}
          p={2}
          key={pin.id}
        >
          <HStack align={'stretch'}>
            <Avatar
              src={currentInstitution.photo ?? ImagePaths.default_school_logo}
            />
            <Div
              verticalAlign={'center'}
              pl={1}
              overflow={'hidden'}
              textAlign={'left'}
            >
              <Text whiteSpace={'nowrap'} fontSize={'md'} fontWeight={'bold'}>
                {currentInstitution.name}
              </Text>
              <Text fontSize={'sm'} fontWeight={'semibold'}>
                Result Checker Pin
              </Text>
            </Div>
          </HStack>
          <Text fontSize={'lg'} fontWeight={'bold'} letterSpacing={1}>
            {pin.pin}
          </Text>
          <Text fontSize={'sm'} lineHeight={'14px'} mt={1}>
            Visit{' '}
            <Text as={'span'} fontWeight={'bold'}>
              {resultCheckerUrl}
            </Text>
          </Text>
          <Text fontSize={'sm'} lineHeight={'14px'} mt={1}>
            Then, enter your Student ID and this Pin
          </Text>
        </Div>
      ))}
    </Div>
  );
}
