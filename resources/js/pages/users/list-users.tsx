import React from 'react';
import { InstitutionUser, User } from '@/types/models';
import { Badge, Button, HStack, Stack, Text } from '@chakra-ui/react';
import route from '@/util/route';
import { InertiaLink } from '@inertiajs/inertia-react';
import useModalToggle from '@/hooks/use-modal-toggle';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import UsersTableFilters from '@/components/table-filters/users-table-filters';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import DateTimeDisplay from '@/components/date-time-display';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';

interface UserWithInstitutions extends User {
  institution_users?: InstitutionUser[];
}

interface Props {
  users: PaginationResponse<UserWithInstitutions>;
}

function ListUsers({ users }: Props) {
  const userFilterToggle = useModalToggle();

  const headers: ServerPaginatedTableHeader<UserWithInstitutions>[] = [
    {
      label: 'Name',
      value: 'full_name',
      render: (row) => {
        return (
          <Button
            as={InertiaLink}
            colorScheme={'brand'}
            size={'sm'}
            fontWeight={'normal'}
            variant={'link'}
            href={route('managers.users.show', [row.id])}
            color={'brand.600'}
          >
            {row.full_name}
          </Button>
        );
      },
      sortKey: 'firstName',
    },
    {
      label: 'Email',
      value: 'email',
      sortKey: 'email',
    },
    {
      label: 'Phone',
      value: 'phone',
    },
    {
      label: 'Manager Role',
      render: (row) =>
        row.roles && row.roles.length > 0
          ? row.roles.map((role) => role.name).join(', ')
          : null,
    },
    {
      label: 'Institutions',
      render: (row) =>
        row.institution_users && row.institution_users.length > 0 ? (
          <Stack spacing={1}>
            {row.institution_users.map((institutionUser) => (
              <Text key={institutionUser.id} fontSize="sm">
                {institutionUser.institution?.name}
              </Text>
            ))}
          </Stack>
        ) : null,
    },
    {
      label: 'Institution Roles',
      render: (row) =>
        row.institution_users && row.institution_users.length > 0 ? (
          <HStack spacing={2} wrap="wrap">
            {row.institution_users.map((institutionUser) => (
              <Badge key={institutionUser.id}>{institutionUser.role}</Badge>
            ))}
          </HStack>
        ) : null,
    },
    {
      label: 'Status',
      render: (row) =>
        row.institution_users && row.institution_users.length > 0 ? (
          <HStack spacing={2} wrap="wrap">
            {row.institution_users.map((institutionUser) => (
              <Badge
                key={institutionUser.id}
                colorScheme={
                  institutionUser.status === 'suspended' ? 'red' : 'green'
                }
              >
                {institutionUser.status}
              </Badge>
            ))}
          </HStack>
        ) : null,
    },
    {
      label: 'Created At',
      value: 'created_at',
      sortKey: 'createdAt',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Action',
      render: (row) => (
        <LinkButton
          href={route('users.impersonate', [row.id])}
          colorScheme={'red'}
          variant={'link'}
          title="Impersonate"
        />
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading title="Users" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={users.data}
            keyExtractor={(row) => row.id}
            validFilters={['role', 'institution_id', 'status']}
            paginator={users}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <UsersTableFilters
        {...userFilterToggle.props}
        showInstitution={true}
        showStatus={true}
      />
    </ManagerDashboardLayout>
  );
}

export default ListUsers;
