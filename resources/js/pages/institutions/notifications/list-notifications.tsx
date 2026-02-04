import React from 'react';
import { InternalNotification } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import DashboardLayout from '@/layout/dashboard-layout';
import NotificationList from '@/pages/notifications/notification-list';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsStaff from '@/hooks/use-is-staff';
import { HStack } from '@chakra-ui/react';

interface Props {
  notifications: PaginationResponse<InternalNotification>;
}

export default function ListNotifications({ notifications }: Props) {
  const { instRoute } = useInstitutionRoute();
  const isStaff = useIsStaff();
  return (
    <DashboardLayout>
      <NotificationList
        notifications={notifications}
        rightElement={
          isStaff ? (
            <HStack>
              <LinkButton
                title="Sent"
                href={instRoute('notifications.sent.index')}
              />
              <LinkButton
                title="New"
                href={instRoute('notifications.create')}
              />
            </HStack>
          ) : (
            <></>
          )
        }
      />
    </DashboardLayout>
  );
}
