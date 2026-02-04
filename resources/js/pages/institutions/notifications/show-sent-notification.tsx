import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import ShowSentNotification from '@/pages/notifications/show-sent-notification';
import { InternalNotification, InternalNotificationRead } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  notification: InternalNotification;
  recipients: PaginationResponse<InternalNotificationRead>;
}

export default function InstitutionShowSentNotification({
  notification,
  recipients,
}: Props) {
  const { instRoute } = useInstitutionRoute();

  return (
    <DashboardLayout>
      <ShowSentNotification
        notification={notification}
        recipients={recipients}
        listUrl={instRoute('notifications.sent.index')}
      />
    </DashboardLayout>
  );
}
