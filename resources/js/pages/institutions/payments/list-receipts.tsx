import React from 'react';
import { Receipt } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';

interface Props {
  receipts: PaginationResponse<Receipt>;
}

export default function ListReceiptTypes({ receipts }: Props) {
  const headers: ServerPaginatedTableHeader<Receipt>[] = [
    {
      label: 'Student',
      value: 'user.full_name',
      render: (row: Receipt) => <DisplayUserFullname user={row.user} />,
    },
    {
      label: 'Category',
      value: 'receiptType.title',
    },
    {
      label: 'Term',
      value: 'term',
    },
    {
      label: 'Session',
      value: 'academic_session.title',
    },
    {
      label: 'Amount',
      value: 'total_amount',
    },
  ];

  return (
    <DashboardLayout>
      <div>
        <Slab>
          <SlabHeading title="List Receipts" />
          <SlabBody>
            <ServerPaginatedTable
              scroll={true}
              headers={headers}
              data={receipts.data}
              keyExtractor={(row) => row.id}
              paginator={receipts}
            />
          </SlabBody>
        </Slab>
      </div>
    </DashboardLayout>
  );
}
