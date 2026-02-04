import React from 'react';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import CreateNotificationForm from '@/pages/notifications/create-notification-form';

export default function CreateNotification() {
  return (
    <ManagerDashboardLayout>
      <CreateNotificationForm
        title="Create Notification"
        submitUrl={route('managers.notifications.store')}
        listUrl={route('managers.notifications.index')}
        allowedTargetTypes={['user']}
      />
    </ManagerDashboardLayout>
  );
}
