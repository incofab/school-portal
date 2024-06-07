import React from 'react';
import { Receipt } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import { Divider, HStack, VStack } from '@chakra-ui/react';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ReceiptTableFilters from '@/components/table-filters/receipt-table-filters';
import useModalToggle from '@/hooks/use-modal-toggle';
import { LabelText } from '@/components/result-helper-components';
import { formatAsCurrency } from '@/util/util';

interface Props {
  receipts: PaginationResponse<Receipt>;
  num_of_payments?: number;
  total_amount_paid?: number;
}

export default function ListReceiptTypes({
  receipts,
  num_of_payments,
  total_amount_paid,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const receiptFilterToggle = useModalToggle();
  const headers: ServerPaginatedTableHeader<Receipt>[] = [
    {
      label: 'Student',
      value: 'user.full_name',
      render: (row: Receipt) => <DisplayUserFullname user={row.user} />,
    },
    {
      label: 'Category',
      value: 'receipt_type.title',
    },
    {
      label: 'Term',
      value: 'term',
    },
    {
      label: 'Session',
      value: 'academic_session.title',
    },
    {
      label: 'Amount',
      value: 'total_amount',
    },
    {
      label: 'Actions',
      render: (row: Receipt) => (
        <HStack spacing={1}>
          <LinkButton
            variant={'ghost'}
            href={instRoute('users.fee-payments.index', [row.user_id, row.id])}
            colorScheme={'brand'}
            title="Payments"
          />
          <LinkButton
            variant={'ghost'}
            href={instRoute('receipts.show', [row.reference])}
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
        <SlabHeading title="List Receipts" />
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
              'studentUser',
              'fee',
              'receiptType',
            ]}
            onFilterButtonClick={receiptFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <ReceiptTableFilters {...receiptFilterToggle.props} />
    </DashboardLayout>
  );
}
