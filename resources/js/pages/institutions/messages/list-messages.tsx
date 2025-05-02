import React from 'react';
import { Message } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import DateTimeDisplay from '@/components/date-time-display';

interface Props {
  messages: PaginationResponse<Message>;
}

export default function ListMessages({ messages }: Props) {
  const { instRoute } = useInstitutionRoute();
  const headers: ServerPaginatedTableHeader<Message>[] = [
    {
      label: 'Sender',
      value: 'sender.full_name',
    },
    {
      label: 'Body',
      value: 'body',
    },
    {
      label: 'Channel',
      value: 'channel',
    },
    {
      label: 'Sent to',
      render: (row) => row.recipient_category,
    },
    {
      label: 'Delivered at',
      render: (row) => <DateTimeDisplay dateTime={row.sent_at} />,
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Messages"
          rightElement={
            <LinkButton title="new" href={instRoute('messages.create')} />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={messages.data}
            keyExtractor={(row) => row.id}
            paginator={messages}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
