import { Div } from '@/components/semantic';
import { Button, Icon, Text } from '@chakra-ui/react';
import React from 'react';
import { ArrowDownIcon } from '@heroicons/react/24/solid';
import { XMarkIcon } from '@heroicons/react/24/outline';

export default function AdmissionApplicationSuccessMessage() {
  return (
    <Div background={'brand.50'} height={'100vh'}>
      <Div
        rounded={'md'}
        border={'1px solid'}
        borderColor={'green.600'}
        bg={'green.50'}
        textAlign={'center'}
        p={8}
      >
        <Text fontSize={'2xl'} color={'green.600'}>
          Application Successful
        </Text>
        <Icon as={ArrowDownIcon} w={10} h={10} mt={6} />
        <Text my={5}>
          Your application will be reviewed and we'll get back to you as soon as
          possible
        </Text>
        <Button
          variant={'outline'}
          colorScheme="brand"
          leftIcon={<Icon as={XMarkIcon} />}
          mt={4}
          size={'sm'}
          onClick={(e) => window.close()}
        >
          Close
        </Button>
      </Div>
    </Div>
  );
}
