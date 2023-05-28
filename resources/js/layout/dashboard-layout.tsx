import React, { PropsWithChildren } from 'react';
// import '../../css/dashboard.css';
import { Div } from '../components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import ImpersonationBanner from '@/layout/impersonation-banner';
import SideBarLayout from './sidebar-layout';
import DashboardHeader from './dashboard-header';

export default function DashboardLayout({ children }: PropsWithChildren) {
  const { isImpersonating } = useSharedProps();

  return (
    <Div>
      {isImpersonating && <ImpersonationBanner />}
      <Div style={{ display: 'flex', height: '100%', minHeight: '100vh' }}>
        <SideBarLayout />
        <Div w={'full'} background={'brand.50'}>
          <DashboardHeader />
          <Div px={'20px'} py={0} color={'#44596e'} mt={3}>
            {children}
          </Div>
        </Div>
      </Div>
    </Div>
  );
}
