import React from 'react';
import { Stack, Text } from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import route from '@/util/route';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';

export default function ImpersonationBanner() {
  const { currentUser, impersonation } = useSharedProps();
  const isGuardian = impersonation?.type === 'guardian';

  return (
    <Div
      bg={isGuardian ? 'orange.500' : 'red.500'}
      bgGradient={isGuardian ? 'linear(to-r, orange.500, pink.500)' : undefined}
      px={8}
      py={isGuardian ? 4 : 1}
      textColor={'white'}
      display={'flex'}
      justifyContent={'space-between'}
      alignItems={'center'}
      boxShadow={isGuardian ? 'md' : 'none'}
    >
      <Stack spacing={0}>
        <Text
          fontSize={isGuardian ? 'md' : 'xs'}
          fontWeight={isGuardian ? 'bold' : 'normal'}
        >
          {isGuardian
            ? `You are mirrowing the page of ${currentUser.full_name} — your child/ward.`
            : `You are signed in as ${currentUser.full_name}`}
        </Text>
        {isGuardian && (
          <Text fontSize={'sm'} opacity={0.9}>
            Your actions will be recorded as theirs. Tread kindly.
          </Text>
        )}
      </Stack>
      <InertiaLink
        href={route('users.impersonate.destroy', [currentUser])}
        method={'delete'}
        as={'button'}
      >
        <Text fontSize={isGuardian ? 'sm' : 'xs'} textDecoration={'underline'}>
          Return to your account
        </Text>
      </InertiaLink>
    </Div>
  );
}
