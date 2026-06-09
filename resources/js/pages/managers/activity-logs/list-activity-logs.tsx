import React from 'react';
import ActivityLogList from '@/components/activity-logs/activity-log-list';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { ActivityLog, Institution, InstitutionGroup } from '@/types/models';
import { PaginationResponse } from '@/types/types';

interface Props {
  activityLogs: PaginationResponse<ActivityLog>;
  institutions: Institution[];
  institutionGroups: InstitutionGroup[];
  filterOptions: {
    categories: string[];
    severities: string[];
    retentionCategories: string[];
  };
  canExport: boolean;
}

export default function ListActivityLogs({
  activityLogs,
  institutions,
  institutionGroups,
  filterOptions,
  canExport,
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
            institutionGroups={institutionGroups}
            showInstitutionFilter={true}
            showInstitutionGroupFilter={true}
            canExport={canExport}
            exportUrl={route('managers.activity-logs.export')}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
