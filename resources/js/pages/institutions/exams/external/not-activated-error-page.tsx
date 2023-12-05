import React from 'react';
import { Div } from '@/components/semantic';
import CenteredBox from '@/components/centered-box';
import { Text } from '@chakra-ui/react';

interface Props {}

export default function NotActivatedErrorPage({}: Props) {
  return (
    <Div background={'brand.50'} minH={'100vh'} p={3}>
      <br />
      <br />
      <CenteredBox
        backgroundColor={'white'}
        px={4}
        py={6}
        shadow={'lg'}
        rounded={'lg'}
      >
        <Text
          fontSize={'3xl'}
          fontWeight={'semibold'}
          color={'red.600'}
          textAlign={'center'}
        >
          App Not Activated!!!
        </Text>
        <br />
        <Text fontSize={'18px'} my={3} lineHeight={'30px'} px={2}>
          You can not take part in this UTME challenge because your JAMB/UTME
          practice app is not activated. Quickly ACTIVATE your app to be
          eligible to be part of this challenge and win exciting prices.
        </Text>
        <br />
        <Text color={'brand.700'} fontSize={'17px'}>
          Contact the phone number or email on your app to activate immediately
        </Text>
      </CenteredBox>
    </Div>
  );
}
