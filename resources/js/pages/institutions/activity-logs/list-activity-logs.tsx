import React from 'react';
import ActivityLogList from '@/components/activity-logs/activity-log-list';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { ActivityLog } from '@/types/models';
import { PaginationResponse } from '@/types/types';

interface Props {
  activityLogs: PaginationResponse<ActivityLog>;
  filterOptions: {
    categories: string[];
    severities: string[];
  };
}

export default function ListActivityLogs({
  activityLogs,
  filterOptions,
}: Props) {
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Activity Logs" />
        <SlabBody>
          <ActivityLogList
            activityLogs={activityLogs}
            filterOptions={filterOptions}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
