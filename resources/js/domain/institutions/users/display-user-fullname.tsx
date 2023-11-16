import React from 'react';
import { Button, ButtonProps } from '@chakra-ui/react';
import { User } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';

export interface Props {
  user?: User;
}

export default function DisplayUserFullname({
  user,
  ...props
}: Props & ButtonProps) {
  const { instRoute } = useInstitutionRoute();
  if (!user) {
    return null;
  }
  return (
    <Button
      as={InertiaLink}
      colorScheme={'brand'}
      size={'sm'}
      {...props}
      fontWeight={'normal'}
      variant={'link'}
      href={instRoute('users.profile', [user.id])}
      color={'brand.700'}
    >
      {user.full_name}
    </Button>
  );
}
