import React, { useState } from 'react';
import WithdrawStatusUpdateModal from '@/components/modals/withdraw-status-update-modal';
import WithdrawalOverviewModal from '@/components/modals/withdrawal-overview-modal';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { PaginationResponse, WithdrawalStatusType } from '@/types/types';
import WithdrawFundModal from '@/components/modals/withdraw-fund-modal';
import ServerPaginatedTable from '@/components/server-paginated-table';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useIsAdminManager from '@/hooks/use-is-admin-manager';
import { BankAccount, Withdrawal } from '@/types/models';
import useModalToggle from '@/hooks/use-modal-toggle';
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
  const withdrawStatusUpdateToggle = useModalToggle();
  const withdrawalOverviewModalToggle = useModalToggle();

  const [withdrawal, setWithdrawal] = useState<Withdrawal>();
  const isAdminManager = useIsAdminManager();

  function openStatusUpdateModal(row: Withdrawal) {
    setWithdrawal(row);
    withdrawStatusUpdateToggle.open();
  }

  function openOverviewModal(row: Withdrawal) {
    setWithdrawal(row);
    withdrawalOverviewModalToggle.open();
  }

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
        row.paid_at ? format(new Date(row.paid_at), 'PPP p') : 
      (isAdminManager ? <BrandButton
        variant="ghost"
        title="Update Status"
        onClick={() => openStatusUpdateModal(row)}
      /> : '-'),
    },

    ...(isAdminManager
      ? [
          {
            label: 'Remark',
            value: 'remark',
          },
        ]
      : []),

    // ...(isAdminManager
    //   ? [
    //       {
    //         label: 'Action',
    //         render: (row: Withdrawal) =>
    //           row.status !== WithdrawalStatusType.Paid &&
    //           row.status !== WithdrawalStatusType.Declined ? (
    //             <BrandButton
    //               colorScheme={'red'}
    //               variant="ghost"
    //               title="Update Status"
    //               onClick={() => openStatusUpdateModal(row)}
    //             />
    //           ) : (
    //             <BrandButton
    //               variant="ghost"
    //               title="Overview"
    //               onClick={() => openOverviewModal(row)}
    //             />
    //           ),
    //       },
    //     ]
    //   : []),
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Withdrawal Requests"
          rightElement={
            !isAdminManager && (
              <HStack>
                <BrandButton
                  title="New Request"
                  onClick={withdrawFundModalToggle.open}
                />
              </HStack>
            )
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
        isInstAdmin={false}
        bankAccounts={bankAccounts}
        {...withdrawFundModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />

      <WithdrawStatusUpdateModal
        withdrawal={withdrawal}
        {...withdrawStatusUpdateToggle.props}
        onSuccess={() => Inertia.reload()}
      />

      <WithdrawalOverviewModal
        withdrawal={withdrawal}
        {...withdrawalOverviewModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
    </ManagerDashboardLayout>
  );
}
