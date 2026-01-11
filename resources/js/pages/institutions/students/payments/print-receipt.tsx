import React from 'react';
import { FeePayment, Receipt, Student } from '@/types/models';
import { Div } from '@/components/semantic';
import {
  Avatar,
  Divider,
  HStack,
  Heading,
  Text,
  VStack,
} from '@chakra-ui/react';
import DataTable, { TableHeader } from '@/components/data-table';
import useSharedProps from '@/hooks/use-shared-props';
import { dateTimeFormat, formatAsCurrency } from '@/util/util';
import { LabelText } from '@/components/result-helper-components';
import ImagePaths from '@/util/images';
import { format } from 'date-fns';
import startCase from 'lodash/startCase';
import DateTimeDisplay from '@/components/date-time-display';
import ReceiptLayout from './receipt-layout';

interface Props {
  receipt: Receipt;
  student: Student;
}

export default function PrintReceiptPage({ receipt, student }: Props) {
  const { currentInstitution, stamp } = useSharedProps();

  const feePaymentTableHeaders: TableHeader<FeePayment>[] = [
    {
      label: 'Paid/Confirmed By',
      value: 'payable.full_name',
      render: (row) =>
        row.payable?.full_name ?? row.confirmed_by?.full_name ?? '',
    },
    {
      label: 'Amount',
      value: 'amount',
    },
    {
      label: 'Reference',
      value: 'reference',
    },
    {
      label: 'Date',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
  ];
  const details = [
    {
      label: 'Date',
      value: format(new Date(receipt.created_at), dateTimeFormat),
    },
    { label: 'Student Name', value: receipt.user?.full_name },
    { label: 'Class', value: student.classification?.title },
    ...(receipt.academic_session || receipt.term
      ? [
          {
            label: 'Session',
            value: `${
              receipt.term ? `${startCase(receipt.term)} Term, ` : ''
            } ${receipt.academic_session?.title}`,
          },
        ]
      : []),
    { label: 'Status', value: receipt.status },
    { label: 'Fee', value: receipt.fee!.amount },
    ...(receipt.amount_remaining > 0
      ? [
          {
            label: 'Pending',
            value: formatAsCurrency(receipt.amount_remaining),
          },
          {
            label: 'Amount Paid',
            value: formatAsCurrency(receipt.amount_paid),
          },
        ]
      : []),
  ];

  return (
    <ReceiptLayout user={student.user!} contentId={'receipt-container'}>
      <Div className="hidden-on-print">
        <br />
        <br />
        <br />
      </Div>
      <Div
        width={'800px'}
        margin="auto"
        p={6}
        boxShadow="md"
        borderRadius="md"
        borderWidth="1px"
        id="receipt-container"
      >
        <HStack>
          <Avatar
            size={'2xl'}
            name="Institution logo"
            src={currentInstitution.photo ?? ImagePaths.default_school_logo}
          />
          <Div textAlign={'center'} width={'full'}>
            <Heading mb={2} fontSize={'x-large'} noOfLines={1}>
              {currentInstitution.name}
            </Heading>
            <Text mb={1} noOfLines={1}>
              {currentInstitution.address}
            </Text>
            <Text fontWeight={'bold'} fontSize={'lg'}>
              {receipt.fee?.title}
            </Text>
          </Div>
        </HStack>
        <Divider mb={5} />
        <VStack spacing={2} mb={4} align={'stretch'}>
          {details.map((detail) => (
            <LabelText
              key={detail.label}
              labelProps={{ width: '130px' }}
              label={detail.label}
              text={detail.value}
            />
          ))}
          {/* <DateTimeDisplay dateTime={receipt.created_at} /> */}
        </VStack>

        <div className="table-container">
          <DataTable
            // scroll={true}
            headers={feePaymentTableHeaders}
            data={receipt.fee_payments!}
            keyExtractor={(row) => row.id}
            hideSearchField={true}
            tableProps={{ className: 'result-table' }}
          />
          <br />
        </div>
        <Text textAlign="right" fontSize="xl">
          <strong>Total Amount:</strong> {formatAsCurrency(receipt.amount_paid)}
        </Text>
        <Divider my={4} />
        <Text textAlign="center">Thank you for your payment!</Text>
      </Div>
    </ReceiptLayout>
  );
}
