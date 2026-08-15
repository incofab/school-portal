import React, { useState } from 'react';
import WithdrawStatusUpdateModal from '@/components/modals/withdraw-status-update-modal';
import WithdrawalOverviewModal from '@/components/modals/withdrawal-overview-modal';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { PaginationResponse } from '@/types/types';
import WithdrawFundModal from '@/components/modals/withdraw-fund-modal';
import ServerPaginatedTable from '@/components/server-paginated-table';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useIsAdminManager from '@/hooks/use-is-admin-manager';
import { BankAccount, Withdrawal } from '@/types/models';
import useModalToggle from '@/hooks/use-modal-toggle';
import { BrandButton } from '@/components/buttons';
import { formatAsCurrency } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import route from '@/util/route';
import {
  AlertDialog,
  AlertDialogBody,
  AlertDialogContent,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogOverlay,
  Button,
  HStack,
  Text,
  useDisclosure,
} from '@chakra-ui/react';
import { format } from 'date-fns';
import { Div } from '@/components/semantic';

interface Props {
  bankAccounts: BankAccount[];
  withdrawals: PaginationResponse<Withdrawal>;
  canRequestWithdrawal: boolean;
}

export default function ListWithdrawals({
  bankAccounts,
  withdrawals,
  canRequestWithdrawal,
}: Props) {
  const withdrawFundModalToggle = useModalToggle();
  const withdrawStatusUpdateToggle = useModalToggle();
  const withdrawalOverviewModalToggle = useModalToggle();

  const [withdrawal, setWithdrawal] = useState<Withdrawal>();
  const [payoutTarget, setPayoutTarget] = useState<Withdrawal | null>(null);
  const isAdminManager = useIsAdminManager();
  const payoutForm = useWebForm({});
  const { toastError, toastSuccess } = useMyToast();
  const confirmation = useDisclosure();
  const cancelRef = React.useRef<HTMLButtonElement>(null);

  function openStatusUpdateModal(row: Withdrawal) {
    setWithdrawal(row);
    withdrawStatusUpdateToggle.open();
  }

  function getWithdrawableName(row: Withdrawal) {
    if (!row.withdrawable) {
      return '-';
    }

    if ('user' in row.withdrawable && row.withdrawable.user?.full_name) {
      return row.withdrawable.user.full_name;
    }

    if ('name' in row.withdrawable) {
      return row.withdrawable.name;
    }

    return '-';
  }

  function openBulkPayoutConfirmation() {
    setPayoutTarget(null);
    confirmation.onOpen();
  }

  function openSinglePayoutConfirmation(row: Withdrawal) {
    setPayoutTarget(row);
    confirmation.onOpen();
  }

  function isPayable(row: Withdrawal) {
    return (
      !row.paid_at && row.status === 'pending' && !row.payout?.is_processing
    );
  }

  async function payWithdrawals() {
    const res = await payoutForm.submit((data, web) =>
      payoutTarget
        ? web.post(route('managers.withdrawals.pay', [payoutTarget]), data)
        : web.post(route('managers.withdrawals.pay-unprocessed'), data)
    );

    if (!res.ok) {
      toastError(res.message ?? 'Unable to process withdrawals');
      return;
    }

    toastSuccess(res.data.message ?? 'Withdrawals processed');
    confirmation.onClose();
    setPayoutTarget(null);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<Withdrawal>[] = [
    {
      label: 'Requested By',
      render: (row) => getWithdrawableName(row),
    },
    {
      label: 'Amount',
      value: 'amount',
      render: (row) => `${formatAsCurrency(row.amount)}`,
    },
    {
      label: 'Status',
      value: 'status',
    },
    ...(isAdminManager
      ? [
          {
            label: 'Payout Status',
            render: (row: Withdrawal) => (
              <Div>
                <Text>{row.payout?.status ?? '-'}</Text>
                {row.payout?.merchant_status ? (
                  <Text color="gray.600" fontSize="sm">
                    {row.payout.merchant_status}
                    {row.payout.is_processing ? ' - processing' : ''}
                  </Text>
                ) : null}
                {row.payout?.note ? (
                  <Text color="gray.600" fontSize="sm">
                    {row.payout.note}
                  </Text>
                ) : null}
                {row.payout?.reference ? (
                  <Text color="gray.500" fontSize="xs">
                    {row.payout.reference}
                  </Text>
                ) : null}
                {row.payout ? (
                  <Text color="gray.500" fontSize="xs">
                    Attempts: {row.payout.attempt_count}
                  </Text>
                ) : null}
              </Div>
            ),
          },
        ]
      : []),
    ...(isAdminManager
      ? [
          {
            label: 'Action',
            render: (row: Withdrawal) =>
              isPayable(row) ? (
                <BrandButton
                  colorScheme="red"
                  variant="ghost"
                  title="Pay"
                  onClick={() => openSinglePayoutConfirmation(row)}
                  isDisabled={payoutForm.processing}
                />
              ) : row.payout?.is_processing ? (
                <Text color="gray.600" fontSize="sm">
                  Processing payout
                </Text>
              ) : (
                '-'
              ),
          },
        ]
      : []),
    {
      label: 'Payout Bank',
      render: (row) =>
        row.bank_account ? (
          <Div>
            <Text>
              {row.bank_account?.bank_name} - {row.bank_account?.account_number}
            </Text>
            <Text>{row.bank_account?.account_name}</Text>
          </Div>
        ) : (
          ''
        ),
    },
    {
      label: 'Request At',
      render: (row) =>
        row.created_at ? format(new Date(row.created_at), 'PPP p') : '-',
    },
    {
      label: 'Settled At',
      render: (row) =>
        row.paid_at ? (
          format(new Date(row.paid_at), 'PPP p')
        ) : isAdminManager ? (
          <BrandButton
            variant="ghost"
            title="Update Status"
            onClick={() => openStatusUpdateModal(row)}
          />
        ) : (
          '-'
        ),
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
            <HStack>
              {isAdminManager ? (
                <BrandButton
                  colorScheme="red"
                  title="Pay Unprocessed Withdrawals"
                  onClick={openBulkPayoutConfirmation}
                  isLoading={payoutForm.processing && !payoutTarget}
                />
              ) : null}
              {canRequestWithdrawal ? (
                <BrandButton
                  title="New Request"
                  onClick={withdrawFundModalToggle.open}
                />
              ) : null}
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
            tableRowProps={(row: Withdrawal) => ({
              backgroundColor: row.paid_at ? 'green.50' : '',
            })}
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

      <AlertDialog
        isOpen={confirmation.isOpen}
        leastDestructiveRef={cancelRef}
        onClose={confirmation.onClose}
      >
        <AlertDialogOverlay>
          <AlertDialogContent>
            <AlertDialogHeader fontSize="lg" fontWeight="bold">
              {payoutTarget
                ? 'Pay this withdrawal?'
                : 'Pay unprocessed withdrawals?'}
            </AlertDialogHeader>
            <AlertDialogBody>
              {payoutTarget
                ? 'This will initiate a real Monnify bank transfer for this withdrawal. Confirm the payout wallet is funded and the bank details have been reviewed.'
                : 'This will initiate real Monnify bank transfers for every eligible unprocessed withdrawal. Confirm the payout wallet is funded and the withdrawal bank details have been reviewed.'}
            </AlertDialogBody>
            <AlertDialogFooter>
              <Button ref={cancelRef} onClick={confirmation.onClose}>
                Cancel
              </Button>
              <Button
                colorScheme="red"
                ml={3}
                onClick={payWithdrawals}
                isLoading={payoutForm.processing}
              >
                {payoutTarget ? 'Pay Withdrawal' : 'Pay Withdrawals'}
              </Button>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialogOverlay>
      </AlertDialog>
    </ManagerDashboardLayout>
  );
}
