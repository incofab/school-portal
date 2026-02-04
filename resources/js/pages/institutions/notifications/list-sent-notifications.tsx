import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import { InternalNotification } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import SentNotificationsList from '@/pages/notifications/list-sent-notifications';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  notifications: PaginationResponse<InternalNotification>;
}

export default function ListSentNotifications({ notifications }: Props) {
  const { instRoute } = useInstitutionRoute();

  return (
    <DashboardLayout>
      <SentNotificationsList
        notifications={notifications}
        createUrl={instRoute('notifications.create')}
        showUrl={(id) => instRoute('notifications.sent.show', [id])}
        deleteUrl={(id) => instRoute('notifications.sent.destroy', [id])}
      />
    </DashboardLayout>
  );
}
