import React, { ReactNode } from 'react';
import { InternalNotification } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DateTimeDisplay from '@/components/date-time-display';
import { Button } from '@chakra-ui/react';

interface Props {
  notifications: PaginationResponse<InternalNotification>;
  rightElement?: ReactNode;
}

export default function NotificationList({
  notifications,
  rightElement,
}: Props) {
  const headers: ServerPaginatedTableHeader<InternalNotification>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Message',
      value: 'body',
    },
    {
      label: 'Sender',
      render: (row) => row.sender_name || '',
    },
    {
      label: 'Created',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Action',
      render: (row) =>
        row.action_url ? (
          <Button
            as="a"
            href={row.action_url}
            size="xs"
            variant="link"
            colorScheme="blue"
          >
            Open
          </Button>
        ) : (
          ''
        ),
    },
  ];

  return (
    <Slab>
      <SlabHeading title="Notifications" rightElement={rightElement} />
      <SlabBody>
        <ServerPaginatedTable
          scroll={true}
          headers={headers}
          data={notifications.data}
          keyExtractor={(row) => row.id}
          paginator={notifications}
          hideSearchField={true}
        />
      </SlabBody>
    </Slab>
  );
}
