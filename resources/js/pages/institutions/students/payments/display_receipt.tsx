import React from 'react';
import { FeePayment, Receipt, Student } from '@/types/models';
import { Div } from '@/components/semantic';
import { Divider, Heading, Stack, Text } from '@chakra-ui/react';
import DataTable, { TableHeader } from '@/components/data-table';
import useSharedProps from '@/hooks/use-shared-props';
import { formatAsCurrency } from '@/util/util';
import DateTimeDisplay from '@/components/date-time-display';

interface Props {
  receipt: Receipt;
  student: Student;
}

function TermReceipt({ receipt, student }: Props) {
  const { currentInstitution, stamp } = useSharedProps();

  const feePaymentTableHeaders: TableHeader<FeePayment>[] = [
    {
      label: 'Fee',
      value: 'fee.title',
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
      label: 'Amount Rem.',
      value: 'amount_remaining',
    },
  ];

  return (
    <Div
      maxWidth="800px"
      margin="auto"
      p={6}
      boxShadow="md"
      borderRadius="md"
      borderWidth="1px"
    >
      <Heading textAlign="center" mb={4}>
        {currentInstitution.name}
      </Heading>
      <Text textAlign="center" mb={2}>
        {currentInstitution.address}
      </Text>
      <Divider mb={4} />
      <Stack spacing={2} mb={4}>
        <Text>
          <strong>Receipt Number:</strong> {receipt.reference}
        </Text>
        <Text>
          <strong>Date:</strong>{' '}
          <DateTimeDisplay dateTime={receipt.created_at} />
        </Text>
        <Text>
          <strong>Student Name:</strong> {student.user?.full_name}
        </Text>
        <Text>
          <strong>Class:</strong> {student.classification?.title}
        </Text>
      </Stack>
      <Divider mb={4} />

      <div className="table-container">
        <DataTable
          scroll={true}
          headers={feePaymentTableHeaders}
          data={receipt.fee_payments!}
          keyExtractor={(row) => row.id}
          hideSearchField={true}
          tableProps={{ className: 'result-table' }}
        />
        <br />
      </div>
      <Divider my={4} />
      <Text textAlign="right" fontSize="xl">
        <strong>Total Amount:</strong> {formatAsCurrency(receipt.total_amount)}
      </Text>
      <Divider my={4} />
      <Text textAlign="center">Thank you for your payment!</Text>
    </Div>
  );
}

export default TermReceipt;
