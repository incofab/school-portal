import React, { PropsWithChildren } from 'react';
import { Div } from '../components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import ImpersonationBanner from '@/layout/impersonation-banner';
import SideBarLayout from './sidebar-layout';
import DashboardHeader from './dashboard-header';
import {
  BoxProps,
  Icon,
  IconButton,
  Spacer,
  Tooltip,
  useColorModeValue,
} from '@chakra-ui/react';
import { ChatBubbleLeftRightIcon } from '@heroicons/react/24/solid';
import { InertiaLink, usePage } from '@inertiajs/inertia-react';
import useInstitutionRoute from '@/hooks/use-institution-route';

export default function DashboardLayout({
  children,
  mainBarProps,
}: PropsWithChildren & { mainBarProps?: BoxProps }) {
  const { isImpersonating, currentInstitution } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const { url } = usePage();
  const assistantRoute = currentInstitution
    ? instRoute('assistant.index')
    : null;
  const isAssistantPage = url.includes('/assistant');
  return (
    <Div>
      {isImpersonating && <ImpersonationBanner />}
      <Div
        style={{
          display: 'flex',
          height: '100%',
          minHeight: '100vh',
          overflowX: 'hidden',
        }}
      >
        <SideBarLayout />
        <Div
          flex={'1'}
          minW={0}
          background={useColorModeValue('brand.50', 'gray.900')}
          overflow={'auto'}
          {...mainBarProps}
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
      {assistantRoute && !isAssistantPage && (
        <Tooltip label="Open EduManager Assistant" placement="left">
          <IconButton
            as={InertiaLink}
            href={assistantRoute}
            aria-label="Open EduManager Assistant"
            icon={<Icon as={ChatBubbleLeftRightIcon} boxSize={6} />}
            colorScheme="brand"
            position="fixed"
            right={{ base: 4, md: 6 }}
            bottom={{ base: 4, md: 6 }}
            size="lg"
            rounded="full"
            boxShadow="0 10px 24px rgba(15, 23, 42, 0.22)"
            zIndex={20}
          />
        </Tooltip>
      )}
    </Div>
  );
}
