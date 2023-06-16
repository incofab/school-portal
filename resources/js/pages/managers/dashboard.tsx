import React from 'react';
import { Text } from '@chakra-ui/react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';

interface Props {
  // users: PaginationResponse<User>;
}

function ManagerDashboard({}: Props) {
  return (
    <ManagerDashboardLayout>
      <Text>This is the dashboard</Text>
    </ManagerDashboardLayout>
  );
}

export default ManagerDashboard;
