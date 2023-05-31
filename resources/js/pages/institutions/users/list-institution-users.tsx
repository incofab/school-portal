import React from 'react';
import { InstitutionUser } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useModalToggle from '@/hooks/use-modal-toggle';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import { InertiaLink } from '@inertiajs/inertia-react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import UsersTableFilters from '@/components/table-filters/users-table-filters';
import route from '@/util/route';

interface Props {
  institutionUsers: PaginationResponse<InstitutionUser>;
}

export default function ListStudents({ institutionUsers }: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();
  const headers: ServerPaginatedTableHeader<InstitutionUser>[] = [
    {
      label: 'Name',
      value: 'user.full_name',
    },
    {
      label: 'Email',
      value: 'user.email',
    },
    {
      label: 'Phone',
      value: 'user.phone',
    },
    {
      label: 'Role',
      value: 'role',
    },
    {
      label: 'Created At',
      value: 'created_at',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            as={InertiaLink}
            aria-label={'Edit user'}
            icon={<Icon as={PencilIcon} />}
            href={instRoute('users.edit', [row.id])}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          {/* <LinkButton
            href={route('users.impersonate', [row.user_id])}
            colorScheme={'red'}
            variant={'link'}
            title="Impersonate"
          /> */}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Staff Members"
          rightElement={
            <LinkButton href={instRoute('users.create')} title={'New'} />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={institutionUsers.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={institutionUsers}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <UsersTableFilters {...userFilterToggle.props} />
    </DashboardLayout>
  );
}