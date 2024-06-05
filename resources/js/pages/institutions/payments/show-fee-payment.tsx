import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import { formatAsCurrency } from '@/util/util';
import { FeePayment, FeePaymentTrack } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { SelectOptionType } from '@/types/types';
import DataTable, { TableHeader } from '@/components/data-table';
import DateTimeDisplay from '@/components/date-time-display';
import { Div } from '@/components/semantic';
import Dt from '@/components/dt';
import { Divider, Text } from '@chakra-ui/react';
import startCase from 'lodash/startCase';

interface Props {
  feePayment: FeePayment;
}

export default function ShowFeePayment({ feePayment }: Props) {
  const contentData: SelectOptionType<string>[] = [
    { label: 'Student', value: feePayment.user!.full_name },
    { label: 'Fee Type', value: feePayment.fee!.title },
    { label: 'Academic Session', value: feePayment.academic_session!.title },
    { label: 'Term', value: startCase(feePayment.term) },
    { label: 'Amount', value: String(feePayment.fee_amount) },
    { label: 'Amount Paid', value: String(feePayment.amount_paid) },
    ...(feePayment.amount_remaining > 0
      ? [{ label: 'Amount Due', value: String(feePayment.amount_remaining) }]
      : []),
  ];
  const feePaymentTracks = feePayment.fee_payment_tracks!;

  const headers: TableHeader<FeePaymentTrack>[] = [
    {
      label: 'Amount',
      value: 'amount',
      render: (row) => formatAsCurrency(row.amount),
    },
    {
      label: 'Transaction Id',
      value: 'transaction_reference',
    },
    {
      label: 'Confirmed by',
      value: 'confirmed_by.full_name',
    },
    {
      label: 'Date',
      value: 'created_at',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    // {
    //   label: 'Action',
    //   render: (row: Fee) => (
    //     <HStack>
    //       <IconButton
    //         aria-label={'Edit Fee'}
    //         icon={<Icon as={PencilIcon} />}
    //         as={InertiaLink}
    //         href={instRoute('fees.edit', [row.id])}
    //         variant={'ghost'}
    //         colorScheme={'brand'}
    //       />
    //       <DestructivePopover
    //         label={'Delete this fee'}
    //         onConfirm={() => deleteItem(row)}
    //         isLoading={deleteForm.processing}
    //       >
    //         <IconButton
    //           aria-label={'Delete fee'}
    //           icon={<Icon as={TrashIcon} />}
    //           variant={'ghost'}
    //           colorScheme={'red'}
    //         />
    //       </DestructivePopover>
    //     </HStack>
    //   ),
    // },
  ];
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={`Fee payment detail`} />
        <SlabBody>
          <Div>
            <Dt contentData={contentData} labelWidth={'160px'} spacing={3} />
            <Divider my={4} />
            <Text fontSize={'sm'} fontWeight={'semibold'}>
              Payments
            </Text>
            <DataTable
              data={feePaymentTracks}
              headers={headers}
              scroll={true}
              keyExtractor={(row) => row.id}
              hideSearchField={true}
            />
          </Div>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
