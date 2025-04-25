import React from 'react';
import { Association, UserAssociation } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import DateTimeDisplay from '@/components/date-time-display';
import { LinkButton } from '@/components/buttons';
import { PaginationResponse } from '@/types/types';

interface Props {
  userAssociations: PaginationResponse<UserAssociation>;
  association?: Association;
}

export default function ListAssociations({
  userAssociations,
  association,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: UserAssociation) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('user-associations.destroy', [obj.id]))
    );

    if (!handleResponseToast(res)) return;

    Inertia.reload({ only: ['userAssociations'] });
  }

  const headers: ServerPaginatedTableHeader<UserAssociation>[] = [
    {
      label: 'Name',
      value: 'institution_user.user.full_name',
    },
    {
      label: 'Date',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Action',
      render: (row: UserAssociation) => (
        <HStack spacing={3}>
          <DestructivePopover
            label={'Delete'}
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
        <SlabHeading
          title={`List User Divisions - ${association?.title}`}
          rightElement={
            <LinkButton
              href={instRoute('user-associations.create')}
              title={'Add Members'}
              colorScheme={'brand'}
            />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={userAssociations.data}
            keyExtractor={(row) => row.id}
            paginator={userAssociations}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
