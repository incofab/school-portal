import { Div } from '@/components/semantic';
import { Button, Icon, Text } from '@chakra-ui/react';
import React from 'react';
import { ArrowDownIcon } from '@heroicons/react/24/solid';
import { XMarkIcon } from '@heroicons/react/24/outline';
import CenteredLayout from '@/components/centered-layout';

interface Props {
  message: string;
  title: string;
}
export default function Message({ title, message }: Props) {
  return (
    <CenteredLayout boxProps={{ maxW: '800px' }}>
      <Text fontSize={'2xl'} color={'green.600'}>
        {title}
      </Text>
      <Icon as={ArrowDownIcon} w={10} h={10} mt={6} />
      <Text
        my={5}
        fontSize={'2xl'}
        dangerouslySetInnerHTML={{ __html: message }}
      />
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
      {/* </Div> */}
    </CenteredLayout>
  );
}
