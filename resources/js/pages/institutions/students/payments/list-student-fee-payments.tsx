import React from 'react';
import { FeePayment, Receipt } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import { Div } from '@/components/semantic';
import DateTimeDisplay from '@/components/date-time-display';
import { formatAsCurrency } from '@/util/util';

interface Props {
  feePayments: PaginationResponse<FeePayment>;
  receipt?: Receipt;
}

export default function ListStudentFeePayments({
  feePayments,
  receipt,
}: Props) {
  const headers: ServerPaginatedTableHeader<FeePayment>[] = [
    {
      label: 'Fee',
      value: 'fee.title',
    },
    {
      label: 'Session',
      value: 'receipt.academic_session.title',
    },
    {
      label: 'Term',
      value: 'receipt.term',
    },
    {
      label: 'Amount',
      value: 'amount',
    },
    {
      label: 'Amount Paid',
      value: 'receipt.amount_paid',
    },
    {
      label: 'Amount Rem.',
      value: 'receipt.amount_remaining',
    },
    {
      label: 'Date',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={receipt?.fee?.title ?? 'My Payments'}
          rightElement={
            <>
              {receipt && (
                <Div fontWeight={'bold'}>
                  Total Amount: {formatAsCurrency(receipt?.amount)}
                </Div>
              )}
            </>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={feePayments.data}
            keyExtractor={(row) => row.id}
            paginator={feePayments}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
