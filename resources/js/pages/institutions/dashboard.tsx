import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Text } from '@chakra-ui/react';

interface Props {
  // users: PaginationResponse<User>;
}

function InstitutionDashboard({}: Props) {
  return (
    <DashboardLayout>
      <Text>This is the dashboard</Text>
    </DashboardLayout>
  );
}

export default InstitutionDashboard;
