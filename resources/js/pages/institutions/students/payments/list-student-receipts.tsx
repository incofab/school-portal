import React from 'react';
import { Receipt, Student, User } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import { HStack } from '@chakra-ui/react';
import { BrandButton, LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import feeableUtil from '@/util/feeable-util';
import UniversalReceiptModal from '@/components/modals/universal-receipt-modal';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  receipts: PaginationResponse<Receipt>;
  student: Student;
}

export default function ListStudentReceipts({ receipts, student }: Props) {
  const { instRoute } = useInstitutionRoute();
  const universalReceiptModalToggle = useModalValueToggle<User | undefined>();

  const headers: ServerPaginatedTableHeader<Receipt>[] = [
    {
      label: 'Fee',
      value: 'fee.title',
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
      label: 'Sectors',
      render: (row) =>
        row.fee?.fee_categories
          ?.map((item) => feeableUtil(item.feeable).getName())
          .join(', ') ?? '',
    },
    {
      label: 'Amount',
      value: 'amount',
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
      label: 'Status',
      value: 'status',
    },
    {
      label: 'Actions',
      render: (row: Receipt) => (
        <HStack spacing={1}>
          <LinkButton
            variant={'ghost'}
            href={instRoute('receipts.print', [row.id])}
            colorScheme={'brand'}
            title="Print"
          />
        </HStack>
      ),
    },
  ];

  return (
    <>
      <DashboardLayout>
        <Slab>
          <SlabHeading
            title="My Receipts"
            rightElement={
              <>
              <BrandButton 
                variant={'ghost'}
                onClick={() => universalReceiptModalToggle.open(student.user)}
                title='Print Universal Receipt'
              />

              <LinkButton
                href={instRoute('students.fee-payments.create', [student.id])}
                title={'Pay Fees'}
              />
              </>
            }
          /> 
          <SlabBody>
            <ServerPaginatedTable
              scroll={true}
              headers={headers}
              data={receipts.data}
              keyExtractor={(row) => row.id}
              paginator={receipts}
              hideSearchField={true}
            />
          </SlabBody>
        </Slab>

        {universalReceiptModalToggle.state && (
          <UniversalReceiptModal
            {...universalReceiptModalToggle.props}
            user={student.user}
            onSuccess={() => {}}
          />
        )}
      </DashboardLayout>
    </>
  );
}
