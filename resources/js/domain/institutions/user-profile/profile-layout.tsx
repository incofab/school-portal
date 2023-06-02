import React, { PropsWithChildren } from 'react';
import { Stack } from '@chakra-ui/react';
import { Div } from '@/components/semantic';
import { PageTitle } from '@/components/page-header';
import SideListLinkNavigation from '@/components/side-list-link-navigation';
import useSharedProps from '@/hooks/use-shared-props';
import { Student, User } from '@/types/models';
import useTypedPage from '@/hooks/use-typed-page';

interface LinkParams {
  label: string;
  routeName: string;
  routeParams?: any[];
  activeRoute?: string;
}

export interface UserProfileLayoutProps {
  user: User;
}

export default function ProfileLayout({ children }: PropsWithChildren<any>) {
  const {
    props: { user },
  } = useTypedPage<UserProfileLayoutProps>();
  const { currentUser, currentInstitution } = useSharedProps();

  // const isStaff = useIsAdmin();

  const links: Array<LinkParams> = [
    {
      label: 'Your Profile',
      routeName: 'institutions.users.profile',
      routeParams: [currentInstitution.uuid, user],
    },
  ];

  // if (currentUser.id === user.id) {
  //   links.push({
  //     label: 'Change Password',
  //     routeName: 'institutions.users.password.edit',
  //     routeParams: [user],
  //   });
  // }

  return (
    <div>
      <PageTitle>
        {user.id === currentUser.id
          ? 'Your Profile'
          : `${user.full_name}'s Profile`}
      </PageTitle>
      <Stack
        direction={{ base: 'column', md: 'row' }}
        spacing={8}
        mt={6}
        align={{ base: 'stretch', md: 'start' }}
      >
        <SideListLinkNavigation links={links} />
        <Div flex={1}>{children}</Div>
      </Stack>
    </div>
  );
}
