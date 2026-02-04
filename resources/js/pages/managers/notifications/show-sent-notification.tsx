import React from 'react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import ShowSentNotification from '@/pages/notifications/show-sent-notification';
import { InternalNotification, InternalNotificationRead } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import route from '@/util/route';

interface Props {
  notification: InternalNotification;
  recipients: PaginationResponse<InternalNotificationRead>;
}

export default function ManagerShowSentNotification({
  notification,
  recipients,
}: Props) {
  return (
    <ManagerDashboardLayout>
      <ShowSentNotification
        notification={notification}
        recipients={recipients}
        listUrl={route('managers.notifications.sent.index')}
      />
    </ManagerDashboardLayout>
  );
}
