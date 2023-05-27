import React from 'react';
import { Text } from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import route from '@/util/route';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';

export default function ImpersonationBanner() {
  const { currentUser } = useSharedProps();

  return (
    <Div
      bg={'red.500'}
      px={8}
      py={1}
      textColor={'white'}
      display={'flex'}
      justifyContent={'space-between'}
      alignItems={'center'}
    >
      <Text fontSize={'xs'}>You're impersonating {currentUser.full_name}</Text>
      <InertiaLink
        href={route('users.impersonate.destroy', [currentUser])}
        method={'delete'}
        as={'button'}
      >
        <Text fontSize={'xs'} textDecoration={'underline'}>
          Stop Impersonating
        </Text>
      </InertiaLink>
    </Div>
  );
}
