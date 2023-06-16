import React from 'react';
import { Institution } from '@/types/models';
import { HStack } from '@chakra-ui/react';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';

interface Props {
  institutions: PaginationResponse<Institution>;
}

export default function ListInstitutions({ institutions }: Props) {
  const headers: ServerPaginatedTableHeader<Institution>[] = [
    {
      label: 'Name',
      value: 'name',
    },
    {
      label: 'Email',
      value: 'email',
    },
    {
      label: 'Phone',
      value: 'phone',
    },
    {
      label: 'Address',
      value: 'address',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <LinkButton
            href={route('users.impersonate', [row.user_id])}
            colorScheme={'red'}
            variant={'link'}
            title="Impersonate"
          />
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading title="Institutions" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={institutions.data}
            keyExtractor={(row) => row.id}
            paginator={institutions}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
