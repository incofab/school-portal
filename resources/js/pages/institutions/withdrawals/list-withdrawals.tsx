import React from 'react';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import WithdrawFundModal from '@/components/modals/withdraw-fund-modal';
import ServerPaginatedTable from '@/components/server-paginated-table';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BankAccount, Withdrawal } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import useModalToggle from '@/hooks/use-modal-toggle';
import { PaginationResponse } from '@/types/types';
import { BrandButton } from '@/components/buttons';
import { formatAsCurrency } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { HStack } from '@chakra-ui/react';
import { format } from 'date-fns';

interface Props {
  bankAccounts: BankAccount[];
  withdrawals: PaginationResponse<Withdrawal>;
}

export default function ListWithdrawals({ bankAccounts, withdrawals }: Props) {
  const withdrawFundModalToggle = useModalToggle();

  const headers: ServerPaginatedTableHeader<Withdrawal>[] = [
    {
      label: 'Amount',
      value: 'amount',
      render: (row) => `${formatAsCurrency(row.amount)}`,
    },
    {
      label: 'Status',
      value: 'status',
    },
    {
      label: 'Payout Bank',
      render: (row) =>
        row.bank_account
          ? `${row.bank_account?.bank_name} - ${row.bank_account?.account_number}`
          : '',
    },
    {
      label: 'Request At',
      render: (row) =>
        row.created_at ? format(new Date(row.created_at), 'PPP p') : '-',
    },
    {
      label: 'Settled At',
      render: (row) =>
        row.paid_at ? format(new Date(row.paid_at), 'PPP p') : '-',
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Withdrawal Requests"
          rightElement={
            <HStack>
              <BrandButton
                title="New Request"
                onClick={withdrawFundModalToggle.open}
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={withdrawals.data}
            keyExtractor={(row) => row.id}
            paginator={withdrawals}
          />
        </SlabBody>
      </Slab>

      <WithdrawFundModal 
        isInstAdmin={true}
        bankAccounts={bankAccounts}
        {...withdrawFundModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
    </DashboardLayout>
  );
}
