import React, { PropsWithChildren } from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import ImpersonationBanner from '@/layout/impersonation-banner';
import { Spacer } from '@chakra-ui/react';
import ManagerSideBarLayout from './manager-sidebar-layout';
import { Div } from '@/components/semantic';
import DashboardHeader from '../dashboard-header';

export default function ManagerDashboardLayout({
  children,
}: PropsWithChildren) {
  const { isImpersonating } = useSharedProps();
  return (
    <Div>
      {isImpersonating && <ImpersonationBanner />}
      <Div style={{ display: 'flex', height: '100%', minHeight: '100vh' }}>
        <ManagerSideBarLayout />
        <Div w={'full'} background={'brand.50'} overflow={'auto'}>
          <DashboardHeader />
          <Div px={'20px'} py={0} color={'#44596e'} mt={3}>
            {children}
            <Spacer height={10} />
          </Div>
        </Div>
      </Div>
    </Div>
  );
}
