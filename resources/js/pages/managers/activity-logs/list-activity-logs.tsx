import React from 'react';
import ActivityLogList from '@/components/activity-logs/activity-log-list';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { ActivityLog, Institution } from '@/types/models';
import { PaginationResponse } from '@/types/types';

interface Props {
  activityLogs: PaginationResponse<ActivityLog>;
  institutions: Institution[];
  filterOptions: {
    categories: string[];
    severities: string[];
  };
}

export default function ListActivityLogs({
  activityLogs,
  institutions,
  filterOptions,
}: Props) {
  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading title="Activity Logs" />
        <SlabBody>
          <ActivityLogList
            activityLogs={activityLogs}
            filterOptions={filterOptions}
            institutions={institutions}
            showInstitutionFilter={true}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
