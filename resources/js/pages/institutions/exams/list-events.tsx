import React from 'react';
import { Event } from '@/types/models';
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
  events: PaginationResponse<Event>;
}

export default function ListEvents({ events }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: Event) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('events.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['events'] });
  }

  const headers: ServerPaginatedTableHeader<Event>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Duration',
      value: 'duration',
    },
    {
      label: 'Starts at',
      value: 'starts_at',
    },
    {
      label: 'Num of Subjects',
      value: 'num_of_subjects',
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Event) => (
              <HStack>
                <LinkButton
                  href={instRoute('event-courseables.index', [row.id])}
                  variant={'link'}
                  title="Content"
                />
                <LinkButton
                  href={instRoute('exams.index', [row.id])}
                  variant={'link'}
                  title="Exams"
                />
                <IconButton
                  aria-label={'Edit Event'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('events.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <DestructivePopover
                  label={'Delete this event'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete event'}
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
          title="List Events"
          rightElement={
            <LinkButton href={instRoute('events.create')} title={'New'} />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={events.data}
            keyExtractor={(row) => row.id}
            paginator={events}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
