import React from 'react';
import { Fee, FeePayment, ReceiptType } from '@/types/models';
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
import UploadFeePaymentModal from '@/components/modals/upload-fee-payment-modal';
import { InertiaLink } from '@inertiajs/inertia-react';
import { LabelText } from '@/components/result-helper-components';
import { formatAsCurrency } from '@/util/util';

interface Props {
  feePayments: PaginationResponse<FeePayment>;
  receiptTypes: ReceiptType[];
  fees: Fee[];
  num_of_payments?: number;
  total_amount_paid?: number;
  pending_amount?: number;
}

export default function ListFeePayments({
  feePayments,
  fees,
  receiptTypes,
  num_of_payments,
  total_amount_paid,
  pending_amount,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const recordFeePaymentModalToggle = useModalToggle();
  const uploadPaymentModalToggle = useModalToggle();
  const feePaymentFilterToggle = useModalToggle();
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
      value: 'user.full_name',
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
      label: 'Balance',
      value: 'amount_remaining',
    },
    {
      label: 'Instalments',
      value: 'fee_payment_tracks_count',
    },
    {
      label: 'Session',
      value: 'academic_session.title',
    },
    {
      label: 'Term',
      value: 'term',
      render: (row) => startCase(row.term),
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: FeePayment) => (
              <HStack>
                <LinkButton
                  variant={'link'}
                  href={instRoute('fee-payments.show', [row])}
                  title="Details"
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
                title={'Upload Payment'}
                onClick={uploadPaymentModalToggle.open}
              />
              <Button
                variant={'solid'}
                colorScheme="brand"
                as={InertiaLink}
                href={instRoute('fee-payments.multi-fee-payment.create')}
                size={'sm'}
              >
                Multi Record Payment
              </Button>
              <BrandButton
                title={'Record Single Payment'}
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
            <LabelText
              label="Total Pending Payment"
              text={formatAsCurrency(pending_amount ?? 0)}
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
      <UploadFeePaymentModal
        receiptTypes={receiptTypes}
        {...uploadPaymentModalToggle.props}
        onSuccess={() => Inertia.reload({ only: ['feePayments'] })}
      />
      <FeePaymentTableFilters {...feePaymentFilterToggle.props} />
    </DashboardLayout>
  );
}
