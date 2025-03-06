import React from 'react';
import {
  AcademicSession,
  Classification,
  Receipt,
  ReceiptType,
  Student,
} from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import { HStack, Text } from '@chakra-ui/react';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Div } from '@/components/semantic';

interface FeeSummary {
  receipt_type: ReceiptType;
  fees_to_pay: {
    amount_paid: number;
    amount_remaining: number;
    title: string;
    is_part_payment: boolean;
  };
  total_amount_to_pay: number;
  total_amount_of_the_receipt_type: number;
}

interface Props {
  receipts: PaginationResponse<Receipt>;
  student: Student;
  classification: Classification;
  term: string;
  academicSession: AcademicSession;

  payableReceiptTypes: FeeSummary[];
  instReceiptTypes: PaginationResponse<ReceiptType>;
}

export default function ListStudentReceiptTypes({
  receipts,
  student,
  classification,
  term,
  academicSession,
  payableReceiptTypes,
  instReceiptTypes,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const headerz: ServerPaginatedTableHeader<ReceiptType>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Total Amount',
      render: (row: ReceiptType) => {
        const feeSummary = payableReceiptTypes.find(
          (feeSummary: FeeSummary) => feeSummary.receipt_type.id === row.id
        );
        return (
          '₦' +
          Number(
            feeSummary?.total_amount_of_the_receipt_type ?? 0
          ).toLocaleString()
        );
      },
    },
    {
      label: 'Amount Paid',
      render: (row: ReceiptType) => {
        const feeSummary = payableReceiptTypes.find(
          (feeSummary: FeeSummary) => feeSummary.receipt_type.id === row.id
        );
        return (
          '₦' +
          Number(
            (feeSummary?.total_amount_of_the_receipt_type ?? 0) -
              (feeSummary?.total_amount_to_pay ?? 0)
          ).toLocaleString()
        );
      },
    },
    {
      label: 'Unpaid Amount',
      render: (row: ReceiptType) => {
        const feeSummary = payableReceiptTypes.find(
          (feeSummary: FeeSummary) => feeSummary.receipt_type.id === row.id
        );
        return (
          '₦' + Number(feeSummary?.total_amount_to_pay ?? 0).toLocaleString()
        );
      },
    },
    {
      label: 'Actions',
      render: (row: ReceiptType) => (
        <HStack spacing={1}>
          <LinkButton
            variant={'ghost'}
            href={instRoute('receipt-type-fees.show', [
              student,
              classification.id,
              term,
              academicSession.id,
            ])}
            colorScheme={'brand'}
            title="View Details"
          />
        </HStack>
      ),
    },
  ];

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
            href={instRoute('students.fee-payments.index', [
              student.id,
              row.id,
            ])}
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
    <>
      <DashboardLayout>
        <Slab>
          <SlabHeading
            title="Payment Summary"
            rightElement={
              <LinkButton
                href={instRoute('students.fee-payments.create', [student])}
                title={'Pay Fees'}
              />
            }
          />
          <SlabBody>
            <Div mb={3}>
              <Text fontSize={'sm'} fontWeight={'medium'}>
                Class : {classification.title}
              </Text>
              <Text fontSize={'sm'} fontWeight={'medium'}>
                Term : {term} term
              </Text>
              <Text fontSize={'sm'} fontWeight={'medium'}>
                Session : {academicSession.title}
              </Text>
            </Div>

            <ServerPaginatedTable
              scroll={true}
              headers={headerz}
              data={instReceiptTypes.data}
              keyExtractor={(row) => row.id}
              paginator={instReceiptTypes}
              hideSearchField={true}
            />
          </SlabBody>
        </Slab>

        {/* 
          <Slab mt={4}>
            <SlabHeading title="Paid Receipts" />
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
        */}
      </DashboardLayout>
    </>
  );
}
