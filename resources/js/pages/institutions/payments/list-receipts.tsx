import React from 'react';
import { Receipt } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import { Divider, HStack, VStack } from '@chakra-ui/react';
import { BrandButton, LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ReceiptTableFilters from '@/components/table-filters/receipt-table-filters';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import { LabelText } from '@/components/result-helper-components';
import { formatAsCurrency } from '@/util/util';
import UniversalReceiptModal from '@/components/modals/universal-receipt-modal';
import DateTimeDisplay from '@/components/date-time-display';
import { dateRangeFilterQueryKeys } from '@/components/table-filters/date-range-filter';

interface Props {
  receipts: PaginationResponse<Receipt>;
  num_of_payments?: number;
  total_amount_paid?: number;
}

export default function ListReceipts({
  receipts,
  num_of_payments,
  total_amount_paid,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const receiptFilterToggle = useModalToggle();
  const universalReceiptModalToggle = useModalValueToggle<any>();

  const headers: ServerPaginatedTableHeader<Receipt>[] = [
    {
      label: 'Student',
      value: 'user.full_name',
      render: (row: Receipt) => <DisplayUserFullname user={row.user} />,
      sortKey: 'student',
    },
    {
      label: 'Fee',
      value: 'fee.title',
      sortKey: 'feeTitle',
    },
    {
      label: 'Amount',
      value: 'amount',
      sortKey: 'amount',
    },
    {
      label: 'Term',
      value: 'term',
      sortKey: 'term',
    },
    {
      label: 'Session',
      value: 'academic_session.title',
    },
    {
      label: 'Balance',
      value: 'amount_remaining',
      sortKey: 'amountRemaining',
    },
    {
      label: 'Status',
      value: 'status',
      sortKey: 'status',
    },
    {
      label: 'Issued on',
      value: 'created_at',
      render: (row: Receipt) => <DateTimeDisplay dateTime={row.created_at} />,
      sortKey: 'createdAt',
    },
    {
      label: 'Actions',
      render: (row: Receipt) => (
        <HStack spacing={1}>
          <LinkButton
            variant={'ghost'}
            href={instRoute('receipts.show', [row.id])}
            colorScheme={'brand'}
            title="View"
          />
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Receipts"
          rightElement={
            <BrandButton
              variant={'ghost'}
              onClick={() => universalReceiptModalToggle.open({})}
              title="Print Universal Receipt"
            />
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
            data={receipts.data}
            keyExtractor={(row) => row.id}
            paginator={receipts}
            validFilters={[
              'term',
              'academicSession',
              'classification',
              'user',
              'fee',
              'status',
              ...dateRangeFilterQueryKeys('created_at'),
            ]}
            onFilterButtonClick={receiptFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <ReceiptTableFilters {...receiptFilterToggle.props} />

      {universalReceiptModalToggle.state && (
        <UniversalReceiptModal
          {...universalReceiptModalToggle.props}
          onSuccess={() => {}}
        />
      )}
    </DashboardLayout>
  );
}
