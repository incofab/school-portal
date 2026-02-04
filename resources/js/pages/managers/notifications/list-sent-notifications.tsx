import React from 'react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { InternalNotification } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import SentNotificationsList from '@/pages/notifications/list-sent-notifications';
import route from '@/util/route';

interface Props {
  notifications: PaginationResponse<InternalNotification>;
}

export default function ListSentNotifications({ notifications }: Props) {
  return (
    <ManagerDashboardLayout>
      <SentNotificationsList
        notifications={notifications}
        createUrl={route('managers.notifications.create')}
        showUrl={(id) => route('managers.notifications.sent.show', [id])}
        deleteUrl={(id) => route('managers.notifications.sent.destroy', [id])}
      />
    </ManagerDashboardLayout>
  );
}
