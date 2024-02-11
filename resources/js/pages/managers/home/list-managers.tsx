import React from 'react';
import { User } from '@/types/models';
import { Button, HStack, Icon, IconButton } from '@chakra-ui/react';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { TrashIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';

interface UserWithMeta extends User {
  partner_institution_groups_count: number;
}
interface Props {
  managers: PaginationResponse<UserWithMeta>;
}

export default function ListManagers({ managers }: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteManager(manager: User) {
    if (!window.confirm('Do you want to delete this Manager?')) {
      return;
    }
    const res = await deleteForm.submit((data, web) =>
      web.delete(route('managers.destroy', [manager]))
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<UserWithMeta>[] = [
    {
      label: 'Name',
      value: 'full_name',
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
      label: 'Groups',
      value: 'partner_institution_groups_count',
    },
    {
      label: 'Role',
      render: (row: UserWithMeta) => row.roles![0].name,
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <LinkButton
            href={route('users.impersonate', [row.id])}
            colorScheme={'red'}
            variant={'link'}
            title="Impersonate"
          />
          <IconButton
            aria-label="Delete Manager"
            colorScheme={'red'}
            icon={<Icon as={TrashIcon} />}
            onClick={() => deleteManager(row)}
            isDisabled={row.partner_institution_groups_count > 0}
          />
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Institutions"
          rightElement={
            <Button
              colorScheme="brand"
              variant={'solid'}
              size={'sm'}
              as={InertiaLink}
              href={route('managers.create')}
            >
              New
            </Button>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={managers.data}
            keyExtractor={(row) => row.id}
            paginator={managers}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
