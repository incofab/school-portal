import { GuardianStudent } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';

interface Props {
  guardians: PaginationResponse<GuardianStudent>;
}

export default function ListGuardians({ guardians }: Props) {
  const headers: ServerPaginatedTableHeader<GuardianStudent>[] = [
    {
      label: 'Name',
      value: 'guardian.full_name',
      render: (row) => <DisplayUserFullname user={row.guardian} />,
    },
    {
      label: 'Student',
      value: 'student.user.full_name',
    },
    {
      label: 'Relationship',
      value: 'relationship',
    },
    {
      label: 'Registered on',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={'Guardians'} />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={guardians.data}
            keyExtractor={(row) => row.id}
            paginator={guardians}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
