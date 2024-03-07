import React from 'react';
import { Text } from '@chakra-ui/react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useIsPartner from '@/hooks/use-is-partner';
import useSharedProps from '@/hooks/use-shared-props';
import route from '@/util/route';
import Slab, { SlabBody } from '@/components/slab';

interface Props {
  // users: PaginationResponse<User>;
}

function ManagerDashboard({}: Props) {
  const isPartner = useIsPartner();
  const { currentUser } = useSharedProps();
  const onboardingUrl = route('registration-requests.create', [currentUser]);
  return (
    <ManagerDashboardLayout>
      {isPartner && (
        <Slab my={2}>
          <SlabBody>
            <Text as={'span'}>Onboarding Link: </Text>{' '}
            <Text
              as={'a'}
              href={onboardingUrl}
              target="_blank"
              color={'brand.500'}
            >
              {onboardingUrl}
            </Text>
          </SlabBody>
        </Slab>
      )}
      <Text>This is the dashboard</Text>
    </ManagerDashboardLayout>
  );
}

export default ManagerDashboard;
