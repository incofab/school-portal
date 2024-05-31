import React from 'react';
import { FeePayment, Receipt } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import { Div } from '@/components/semantic';

interface Props {
  feePayments: PaginationResponse<FeePayment>;
  receipt: Receipt;
}

function ListStudentFeePayments({ feePayments, receipt }: Props) {
  const headers: ServerPaginatedTableHeader<FeePayment>[] = [
    {
      label: 'Fee',
      value: 'fee.title',
    },
    {
      label: 'Session',
      value: 'academicSession.title',
    },
    {
      label: 'Term',
      value: 'term',
    },
    {
      label: 'Fee Amount',
      value: 'fee_amount',
    },
    {
      label: 'Amount Paid',
      value: 'amount_paid',
    },
    {
      label: 'Amount Rem.',
      value: 'amount_remaining',
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={receipt.receipt_type?.title ?? 'My Payments'}
          rightElement={
            <Div fontWeight={'bold'}>Total Amount: {receipt.total_amount}</Div>
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

export default ListStudentFeePayments;
