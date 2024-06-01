import React from 'react';
import { ReceiptType } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { Nullable, PaginationResponse } from '@/types/types';
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
import CreateEditReceiptTypeModal from '@/components/modals/create-edit-receipt-type-modal';

interface Props {
  receiptTypes: PaginationResponse<ReceiptType>;
}

export default function ListReceiptTypes({ receiptTypes }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const createReceiptTypeModal = useModalValueToggle<Nullable<ReceiptType>>();

  async function deleteItem(obj: ReceiptType) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('receipt-types.destroy', [obj.id]))
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<ReceiptType>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Description',
      value: 'description',
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: ReceiptType) => (
              <HStack spacing={3}>
                <IconButton
                  aria-label={'Edit Fee Category'}
                  icon={<Icon as={PencilIcon} />}
                  variant={'ghost'}
                  colorScheme={'brand'}
                  onClick={() => createReceiptTypeModal.open(row)}
                />

                <DestructivePopover
                  label={'Delete this group'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete Fee Category'}
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
          title="List Receipt Categories"
          rightElement={
            <HStack>
              <BrandButton
                title="Add New"
                onClick={() => createReceiptTypeModal.open(null)}
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={receiptTypes.data}
            keyExtractor={(row) => row.id}
            paginator={receiptTypes}
          />
        </SlabBody>
      </Slab>
      {createReceiptTypeModal.state && (
        <CreateEditReceiptTypeModal
          {...createReceiptTypeModal.props}
          receiptType={createReceiptTypeModal.state}
          onSuccess={() => Inertia.reload({ only: ['receiptTypes'] })}
        />
      )}
      {createReceiptTypeModal.state == null && (
        <CreateEditReceiptTypeModal
          {...createReceiptTypeModal.props}
          receiptType={createReceiptTypeModal.state}
          onSuccess={() => Inertia.reload({ only: ['receiptTypes'] })}
        />
      )}
    </DashboardLayout>
  );
}
