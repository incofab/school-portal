import React from 'react';
import { Association, Classification } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import DateTimeDisplay from '@/components/date-time-display';
import DataTable from '@/components/data-table';
import CreateEditAssociationModal from '@/components/modals/create-edit-association-modal';

interface Props {
  associations: Association[];
}

export default function ListAssociations({ associations }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const associationModalToggle = useModalValueToggle<Association | undefined>();

  async function deleteItem(obj: Association) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('associations.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['associations'] });
  }

  const headers: ServerPaginatedTableHeader<Association>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Description',
      value: 'description',
    },
    {
      label: 'Members',
      value: 'user_associations_count',
    },
    {
      label: 'Created At',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Classification) => (
              <HStack spacing={3}>
                <LinkButton
                  href={instRoute('user-associations.index', [row.id])}
                  variant={'link'}
                  colorScheme={'brand'}
                  title={'View Members'}
                />
                <IconButton
                  aria-label={'Edit'}
                  icon={<Icon as={PencilIcon} />}
                  variant={'ghost'}
                  colorScheme={'brand'}
                  onClick={() => associationModalToggle.open(row)}
                />
                <DestructivePopover
                  label={'Delete'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete Class'}
                    icon={<Icon as={TrashIcon} />}
                    variant={'ghost'}
                    colorScheme={'red'}
                  />
                </DestructivePopover>
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List User Divisions"
          rightElement={
            <HStack>
              {isAdmin && (
                <>
                  <BrandButton
                    title="Add"
                    onClick={() => associationModalToggle.open(undefined)}
                  />
                </>
              )}
            </HStack>
          }
        />
        <SlabBody>
          <DataTable
            scroll={true}
            headers={headers}
            data={associations}
            keyExtractor={(row) => row.id}
          />
        </SlabBody>
      </Slab>
      {associationModalToggle.state !== null && (
        <CreateEditAssociationModal
          {...associationModalToggle.props}
          association={associationModalToggle.state}
          onSuccess={() => Inertia.reload({ only: ['associations'] })}
        />
      )}
    </DashboardLayout>
  );
}
