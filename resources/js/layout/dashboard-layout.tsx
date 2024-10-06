import React, { PropsWithChildren } from 'react';
import { Div } from '../components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import ImpersonationBanner from '@/layout/impersonation-banner';
import SideBarLayout from './sidebar-layout';
import DashboardHeader from './dashboard-header';
import { Spacer, useColorModeValue } from '@chakra-ui/react';

export default function DashboardLayout({ children }: PropsWithChildren) {
  const { isImpersonating } = useSharedProps();
  return (
    <Div>
      {isImpersonating && <ImpersonationBanner />}
      <Div style={{ display: 'flex', height: '100%', minHeight: '100vh' }}>
        <SideBarLayout />
        <Div
          w={'full'}
          background={useColorModeValue('brand.50', 'gray.900')}
          overflow={'auto'}
        >
          <DashboardHeader />
          <Div
            px={'20px'}
            py={0}
            color={useColorModeValue('#44596e', 'gray.300')}
            mt={3}
          >
            {children}
            <Spacer height={10} />
          </Div>
        </Div>
      </Div>
    </Div>
  );
}
