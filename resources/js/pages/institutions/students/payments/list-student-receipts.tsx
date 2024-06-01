import React from 'react';
import { Receipt } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import { HStack } from '@chakra-ui/react';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  receipts: PaginationResponse<Receipt>;
}

export default function ListStudentReceiptTypes({ receipts }: Props) {
  const { instRoute } = useInstitutionRoute();
  const headers: ServerPaginatedTableHeader<Receipt>[] = [
    {
      label: 'Category',
      value: 'receipt_type.title',
    },
    {
      label: 'Session',
      value: 'academic_session.title',
    },
    {
      label: 'Term',
      value: 'term',
    },
    {
      label: 'Class',
      value: 'classification.title',
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
      <div>
        <Slab>
          <SlabHeading title="My Receipts Receipts" />
          <SlabBody>
            <ServerPaginatedTable
              scroll={true}
              headers={headers}
              data={receipts.data}
              keyExtractor={(row) => row.id}
              paginator={receipts}
            />
          </SlabBody>
        </Slab>
      </div>
    </DashboardLayout>
  );
}
