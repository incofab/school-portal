import React from 'react';
import { User } from '@/types/models';
import {
  Button,
  HStack,
  IconButton,
  Spacer,
  Text,
  Icon,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { format } from 'date-fns';
import { dateFormat } from '@/util/util';
import { InertiaLink } from '@inertiajs/inertia-react';
import { Inertia } from '@inertiajs/inertia';
import route from '@/util/route';
import useModalToggle from '@/hooks/use-modal-toggle';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { PageTitle } from '@/components/page-header';
import { LinkButton } from '@/components/buttons';
import UsersTableFilters from '@/components/table-filters/users-table-filters';

interface Props {
  users: PaginationResponse<User>;
}

function ListUsers({ users }: Props) {
  const userFilterToggle = useModalToggle();

  const headers = [
    {
      label: 'Name',
      value: 'full_name',
    },
    {
      label: 'Email',
      value: 'email',
    },
    {
      label: 'Role',
      value: 'role',
    },
    {
      label: 'Phone',
      value: 'phone',
    },
    // {
    //   label: 'Created At',
    //   value: 'created_at',
    //   render: (row: User) => (
    //     <Text>{format(new Date(row.created_at), dateFormat)}</Text>
    //   ),
    // },
    {
      label: 'Action',
      render: (row: User) => (
        <HStack>
          <IconButton
            aria-label={'Edit user'}
            icon={<Icon as={PencilIcon} />}
            onClick={() => Inertia.visit(route('users.edit', [row]))}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          <Button
            as={InertiaLink}
            href={route('users.impersonate', [row])}
            colorScheme={'red'}
            variant={'link'}
            size={'sm'}
            fontWeight={'normal'}
          >
            Impersonate
          </Button>
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading>
          <HStack>
            <PageTitle>List of all users</PageTitle>
            <Spacer />
            <LinkButton href={route('users.create')} title={'New'} />
          </HStack>
        </SlabHeading>
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={users.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={users}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <UsersTableFilters {...userFilterToggle.props} />
    </DashboardLayout>
  );
}

export default ListUsers;
