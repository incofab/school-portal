import React from 'react';
import ActivityLogList from '@/components/activity-logs/activity-log-list';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { ActivityLog } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';

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
  const { instRoute } = useInstitutionRoute();
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
