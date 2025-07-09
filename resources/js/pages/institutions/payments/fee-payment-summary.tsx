import React from 'react';
import { AcademicSession, Classification, Fee, Student } from '@/types/models';
import { HStack, VStack, Divider, Icon, Button } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { TermType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LabelText } from '@/components/result-helper-components';
import DataTable, { TableHeader } from '@/components/data-table';
import { CloudArrowDownIcon } from '@heroicons/react/24/outline';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { numberFormat } from '@/util/util';

interface FeePaymentSummary {
  student: Student;
  payment_summaries: {
    amount_remaining: number;
    amount_paid: number;
    title: string;
    is_part_payment: boolean;
    fee_id: number;
  }[];
  //{[key: string]: string};
  total_amount_to_pay: number;
}
interface Props {
  feePaymentSummaries: FeePaymentSummary[];
  fees: Fee[];
  term: TermType;
  academicSession: AcademicSession;
  classification: Classification;
}

export default function feePaymentSummary({
  feePaymentSummaries,
  fees,
  classification,
  academicSession,
  term,
}: Props) {
  const { instRoute } = useInstitutionRoute();

  const headers: TableHeader<FeePaymentSummary>[] = [
    {
      label: 'Student',
      value: 'student.user.full_name',
    },
    ...fees.map((fee) => ({
      label: `${fee.title} (${numberFormat(fee.amount)})`,
      render: (item: FeePaymentSummary) => {
        console.log('item', fee.id, item);

        const summary = item.payment_summaries.find(
          (payment) => payment.fee_id === fee.id
        );
        return numberFormat(summary?.amount_paid ?? 0);
      },
    })),
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Fees"
          rightElement={
            <HStack>
              <Button
                as={'a'}
                title={'Download'}
                leftIcon={<Icon as={CloudArrowDownIcon} />}
                href={instRoute('fee-payments.summary', {
                  download: true,
                  term: term,
                  academic_session_id: academicSession.id,
                  classification_id: classification.id,
                })}
                variant={'solid'}
                colorScheme={'brand'}
              >
                Download
              </Button>
            </HStack>
          }
        />
        <SlabBody>
          <VStack align={'stretch'}>
            <LabelText label="Class" text={classification.title} />
            <LabelText
              label="Term/Session"
              text={`${term} Term, ${academicSession.title}`}
            />
          </VStack>
          <Divider my={3} />
          <DataTable
            scroll={true}
            headers={headers}
            data={feePaymentSummaries}
            keyExtractor={(row) => row.student.id}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
