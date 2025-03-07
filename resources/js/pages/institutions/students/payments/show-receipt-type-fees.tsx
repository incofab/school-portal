import React from 'react';
import {
  FeesToPay,
  FeeSummary,
  Student,
} from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Text } from '@chakra-ui/react';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import DataTable, { TableHeader } from '@/components/data-table';
import { Div } from '@/components/semantic';
import CenteredBox from '@/components/centered-box';

interface Props {
  student: Student;
  fees: FeesToPay[];
  feeSummary: FeeSummary;
}

export default function ShowReceiptTypeFees({
  student,
  fees,
  feeSummary,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const headers: TableHeader<FeesToPay>[] = [
    {
      label: 'Title',
      render: (row: FeesToPay) => row.title,
    },
    {
      label: 'Total Amount',
      render: (row: FeesToPay) => {
        return '₦' + (row.amount_paid + row.amount_remaining).toLocaleString();
      },
    },
    {
      label: 'Amount Paid',
      render: (row: FeesToPay) => {
        return '₦' + row.amount_paid.toLocaleString();
      },
    },
    {
      label: 'Unpaid Amount',
      render: (row: FeesToPay) => {
        return '₦' + row.amount_remaining.toLocaleString();
      },
    },
  ];

  return (
    <>
      <DashboardLayout>
        <CenteredBox>
          <Slab>
            <SlabHeading
              title="Receipt Fee Summary"
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
                  Receipt Title: {feeSummary.receipt_type.title}
                </Text>
                <Text fontSize={'sm'} fontWeight={'medium'}>
                  Total Amount:{' '}
                  {'₦' +
                    feeSummary.total_amount_of_the_receipt_type.toLocaleString()}
                </Text>
                <Text fontSize={'sm'} fontWeight={'medium'}>
                  Total Paid :{' '}
                  {'₦' +
                    (
                      feeSummary.total_amount_of_the_receipt_type -
                      feeSummary.total_amount_to_pay
                    ).toLocaleString()}
                </Text>
                <Text fontSize={'sm'} fontWeight={'medium'}>
                  Total UnPaid :{' '}
                  {'₦' + feeSummary.total_amount_to_pay.toLocaleString()}
                </Text>
              </Div>

              <Div className="table-container">
                <DataTable
                  scroll={true}
                  data={fees}
                  headers={headers}
                  keyExtractor={(row) => row.id}
                  hideSearchField={true}
                />
              </Div>
            </SlabBody>
          </Slab>
        </CenteredBox>
      </DashboardLayout>
    </>
  );
}
