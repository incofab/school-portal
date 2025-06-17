import React from 'react';
import { Fee, FeePayment } from '@/types/models';
import {
  HStack,
  IconButton,
  Icon,
  Button,
  VStack,
  Divider,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import RecordFeePaymentModal from '@/components/modals/record-fee-payment-modal';
import useModalToggle from '@/hooks/use-modal-toggle';
import FeePaymentTableFilters from '@/components/table-filters/fee-payment-table-filters';
import startCase from 'lodash/startCase';
import { InertiaLink } from '@inertiajs/inertia-react';
import { LabelText } from '@/components/result-helper-components';
import { formatAsCurrency } from '@/util/util';
import RetrievePaymentSummaryModal from '@/components/modals/retrieve-payment-summary-modal';

interface Props {
  feePayments: PaginationResponse<FeePayment>;
  fees: Fee[];
  num_of_payments?: number;
  total_amount_paid?: number;
}

export default function ListFeePayments({
  feePayments,
  fees,
  num_of_payments,
  total_amount_paid,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const recordFeePaymentModalToggle = useModalToggle();
  const feePaymentFilterToggle = useModalToggle();
  const retrievePaymentSummaryModal = useModalToggle();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: FeePayment) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('fee-payments.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['feePayments'] });
  }

  const headers: ServerPaginatedTableHeader<FeePayment>[] = [
    {
      label: 'Fee',
      value: 'fee.title',
    },
    {
      label: 'Student',
      value: 'receipt.user.full_name',
    },
    {
      label: 'Fee Amount',
      value: 'fee.amount',
    },
    {
      label: 'Amount Paid',
      value: 'receipt.amount_paid',
    },
    {
      label: 'Balance',
      value: 'receipt.amount_remaining',
    },
    {
      label: 'Confirmed By',
      value: 'confirmed_by.full_name',
    },
    {
      label: 'Session',
      value: 'receipt.academic_session.title',
    },
    {
      label: 'Term',
      value: 'term',
      render: (row) => startCase(row.receipt?.term),
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: FeePayment) => (
              <HStack>
                <LinkButton
                  variant={'link'}
                  href={instRoute('receipts.show', [row.receipt_id])}
                  title="Receipt"
                />
                <DestructivePopover
                  label={'Delete this fee'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete payment'}
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
          title="List Fees"
          rightElement={
            <HStack>
              <BrandButton
                title={'Payment Summary'}
                onClick={retrievePaymentSummaryModal.open}
                variant={'outline'}
              />
              <BrandButton
                title={'Record Payment'}
                onClick={recordFeePaymentModalToggle.open}
              />
            </HStack>
          }
        />
        <SlabBody>
          <VStack align={'stretch'}>
            <LabelText label="Number of Payments" text={num_of_payments} />
            <LabelText
              label="Total Amount Paid"
              text={formatAsCurrency(total_amount_paid ?? 0)}
            />
          </VStack>
          <Divider my={3} />
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={feePayments.data}
            keyExtractor={(row) => row.id}
            paginator={feePayments}
            validFilters={['fee', 'user', 'academicSession', 'term']}
            onFilterButtonClick={feePaymentFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <RecordFeePaymentModal
        fees={fees}
        {...recordFeePaymentModalToggle.props}
        onSuccess={() => Inertia.reload({ only: ['feePayments'] })}
      />
      <FeePaymentTableFilters {...feePaymentFilterToggle.props} />
      <RetrievePaymentSummaryModal {...retrievePaymentSummaryModal.props} />
    </DashboardLayout>
  );
}
