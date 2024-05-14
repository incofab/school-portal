import React from 'react';
import { Classification, Student, User } from '@/types/models';
import { HStack, IconButton, Icon, Button } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { BookOpenIcon, TrashIcon } from '@heroicons/react/24/solid';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';

interface Dependent extends Student {
  user: User;
  classification: Classification;
}

interface Props {
  dependents: PaginationResponse<Dependent>;
}

function ListDependents({ dependents }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const deleteForm = useWebForm({});

  async function deleteItem(obj: Dependent) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('guardians.remove-dependent', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['dependents'] });
  }

  const headers: ServerPaginatedTableHeader<Dependent>[] = [
    {
      label: 'Name',
      value: 'full_name',
      render: (row) => <DisplayUserFullname user={row.user} />,
    },
    {
      label: 'Class',
      value: 'classification.title',
    },
    {
      label: 'Student Id',
      value: 'code',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <Button
            as={InertiaLink}
            leftIcon={<Icon as={BookOpenIcon} />}
            href={instRoute('term-results.index', [row.user_id])}
            variant={'link'}
            colorScheme={'brand'}
          >
            Results
          </Button>
          <DestructivePopover
            label={`Remove ${row.user.full_name} as your child/ward?`}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="List Students" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={dependents.data}
            keyExtractor={(row) => row.id}
            paginator={dependents}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

export default ListDependents;
