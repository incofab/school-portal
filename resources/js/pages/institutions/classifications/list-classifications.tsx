import React from 'react';
import { Classification } from '@/types/models';
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
  classifications: PaginationResponse<Classification>;
}

export default function ListClassification({ classifications }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: Classification) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('classifications.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['classifications'] });
  }

  const headers: ServerPaginatedTableHeader<Classification>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    // {
    //   label: 'Created At',
    //   value: 'created_at',
    //   render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    // },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Classification) => (
              <HStack>
                <IconButton
                  aria-label={'Edit Class'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('classifications.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <DestructivePopover
                  label={'Delete this class'}
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
          title="List Classes"
          rightElement={
            <LinkButton
              href={instRoute('classifications.create')}
              title={'New'}
            />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={classifications.data}
            keyExtractor={(row) => row.id}
            paginator={classifications}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
