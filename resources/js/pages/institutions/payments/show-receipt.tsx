import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import { formatAsCurrency } from '@/util/util';
import { FeePayment, Receipt } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { SelectOptionType } from '@/types/types';
import DataTable, { TableHeader } from '@/components/data-table';
import DateTimeDisplay from '@/components/date-time-display';
import { Div } from '@/components/semantic';
import Dt from '@/components/dt';
import { Divider, Text } from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  receipt: Receipt;
}

export default function ShowReceipt({ receipt }: Props) {
  const { instRoute } = useInstitutionRoute();
  const contentData: SelectOptionType<string>[] = [
    { label: 'Student', value: receipt.user!.full_name },
    { label: 'Fee Type', value: receipt.fee!.title },
    { label: 'Academic Session', value: receipt.academic_session?.title },
    { label: 'Term', value: startCase(receipt.term) },
    { label: 'Amount', value: String(receipt.amount) },
    { label: 'Amount Paid', value: String(receipt.amount_paid) },
    ...(receipt.amount_remaining > 0
      ? [{ label: 'Amount Due', value: String(receipt.amount_remaining) }]
      : []),
  ];
  const feePayments = receipt.fee_payments!;

  const headers: TableHeader<FeePayment>[] = [
    {
      label: 'Paid By',
      value: 'payable.full_name',
    },
    {
      label: 'Amount',
      value: 'amount',
      render: (row) => formatAsCurrency(row.amount),
    },
    {
      label: 'Transaction Id',
      value: 'reference',
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
  ];
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`Receipt Details`}
          rightElement={
            <LinkButton
              href={instRoute('receipts.print', [receipt.id])}
              title="Print"
              variant={'link'}
            />
          }
        />
        <SlabBody>
          <Div>
            <Dt contentData={contentData} labelWidth={'160px'} spacing={3} />
            <Divider my={4} />
            <Text fontSize={'sm'} fontWeight={'semibold'}>
              Payments
            </Text>
            <DataTable
              data={feePayments}
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
