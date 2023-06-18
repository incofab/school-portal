import React from 'react';
import { Fee, FeePayment } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
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

interface Props {
  feePayments: PaginationResponse<FeePayment>;
  fees: Fee[];
}

export default function ListFeePayments({ feePayments, fees }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const recordFeePaymentModalToggle = useModalToggle();
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
      label: 'Amount Remaining',
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
            <BrandButton
              title={'Record Payment'}
              onClick={recordFeePaymentModalToggle.open}
            />
          }
        />
        <SlabBody>
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
    </DashboardLayout>
  );
}
