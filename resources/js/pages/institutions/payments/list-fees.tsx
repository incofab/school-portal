import React from 'react';
import { Fee } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';

interface Props {
  fees: PaginationResponse<Fee>;
}

export default function ListFees({ fees }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: Fee) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('fees.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['fees'] });
  }

  const headers: ServerPaginatedTableHeader<Fee>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Amount',
      value: 'amount',
    },
    {
      label: 'Interval',
      value: 'payment_interval',
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Fee) => (
              <HStack>
                <IconButton
                  aria-label={'Edit Fee'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('fees.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <DestructivePopover
                  label={'Delete this fee'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete fee'}
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
          title="List Fees"
          rightElement={
            <LinkButton href={instRoute('fees.create')} title={'New'} />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={fees.data}
            keyExtractor={(row) => row.id}
            paginator={fees}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
