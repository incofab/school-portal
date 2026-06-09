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
    retentionCategories: string[];
  };
  canExport: boolean;
}

export default function ListActivityLogs({
  activityLogs,
  filterOptions,
  canExport,
}: Props) {
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Activity Logs" />
        <SlabBody>
          <ActivityLogList
            activityLogs={activityLogs}
            filterOptions={filterOptions}
            canExport={canExport}
            exportUrl={instRoute('activity-logs.export')}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
