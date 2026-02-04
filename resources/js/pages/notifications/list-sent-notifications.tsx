import React from 'react';
import { InternalNotification } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DateTimeDisplay from '@/components/date-time-display';
import { HStack, Icon, IconButton, Text } from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import { EyeIcon } from '@heroicons/react/24/outline';
import { TrashIcon } from '@heroicons/react/24/solid';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import SentNotificationTableFilters from '@/components/table-filters/sent-notification-table-filters';
import useModalToggle from '@/hooks/use-modal-toggle';
import { LinkButton } from '@/components/buttons';

interface Props {
  notifications: PaginationResponse<InternalNotification>;
  createUrl: string;
  showUrl(id: number): string;
  deleteUrl(id: number): string;
}

export default function SentNotificationsList({
  notifications,
  createUrl,
  showUrl,
  deleteUrl,
}: Props) {
  const filterToggle = useModalToggle();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteNotification(row: InternalNotification) {
    const res = await deleteForm.submit((_, web) =>
      web.delete(deleteUrl(row.id))
    );
    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['notifications'] });
  }

  const headers: ServerPaginatedTableHeader<InternalNotification>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Type',
      render: (row) => row.type ?? '-',
    },
    {
      label: 'Read',
      render: (row) => <Text>{row.reads_count ?? 0}</Text>,
    },
    {
      label: 'Created',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            as={InertiaLink}
            href={showUrl(row.id)}
            aria-label={'View notification'}
            icon={<Icon as={EyeIcon} />}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          {row.reads_count === 0 && (
            <DestructivePopover
              label={`Delete ${row.title}?`}
              onConfirm={() => deleteNotification(row)}
              isLoading={deleteForm.processing}
            >
              <IconButton
                aria-label={'Delete notification'}
                icon={<Icon as={TrashIcon} />}
                variant={'ghost'}
                colorScheme={'red'}
              />
            </DestructivePopover>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <Slab>
      <SlabHeading
        title="Sent Notifications"
        rightElement={<LinkButton title="new" href={createUrl} />}
      />
      <SlabBody>
        <ServerPaginatedTable
          scroll={true}
          headers={headers}
          data={notifications.data}
          keyExtractor={(row) => row.id}
          paginator={notifications}
          validFilters={['search', 'type', 'fromDate', 'toDate']}
          onFilterButtonClick={filterToggle.open}
          hideSearchField={true}
        />
      </SlabBody>
      <SentNotificationTableFilters {...filterToggle.props} />
    </Slab>
  );
}
