import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import CreateNotificationForm from '@/pages/notifications/create-notification-form';
import useInstitutionRoute from '@/hooks/use-institution-route';

export default function CreateNotification() {
  const { instRoute } = useInstitutionRoute();
  return (
    <DashboardLayout>
      <CreateNotificationForm
        title="Create Notification"
        submitUrl={instRoute('notifications.store')}
        listUrl={instRoute('notifications.index')}
        allowedTargetTypes={[
          'institution-user',
          'student',
          'classification',
          'classification-group',
          'user',
        ]}
      />
    </DashboardLayout>
  );
}
