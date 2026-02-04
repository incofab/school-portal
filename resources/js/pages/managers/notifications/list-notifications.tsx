import React from 'react';
import { InternalNotification } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import NotificationList from '@/pages/notifications/notification-list';
import { LinkButton } from '@/components/buttons';
import route from '@/util/route';
import { HStack } from '@chakra-ui/react';

interface Props {
  notifications: PaginationResponse<InternalNotification>;
}

export default function ListNotifications({ notifications }: Props) {
  return (
    <ManagerDashboardLayout>
      <NotificationList
        notifications={notifications}
        rightElement={
          <HStack>
            <LinkButton
              title="Sent"
              href={route('managers.notifications.sent.index')}
            />
            <LinkButton
              title="New"
              href={route('managers.notifications.create')}
            />
          </HStack>
        }
      />
    </ManagerDashboardLayout>
  );
}
