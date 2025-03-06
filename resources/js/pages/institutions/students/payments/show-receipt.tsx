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
import ResultSheetLayout from '../../result-sheets/result-sheet-layout';
import startCase from 'lodash/startCase'; 

interface Props {
  receipt: Receipt;
  student: Student;
}

export default function ShowReceipt({ receipt, student }: Props) {
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
    {
      label: 'Teller No',
      render: (feePayment) =>
        (feePayment.fee_payment_tracks
          ? feePayment.fee_payment_tracks[0]?.transaction_reference
          : '') ?? '',
    },
    // {
    //   label: 'Reference',
    //   value: 'reference',
    // },
  ];
  const details = [
    {
      label: 'Date',
      value: format(new Date(receipt.created_at), dateTimeFormat),
    },
    { label: 'Student Name', value: receipt.user?.full_name },
    { label: 'Class', value: receipt.classification?.title },
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
    // ...(receipt.term
    //   ? [{ label: 'Term', value: startCase(receipt.term) }]
    //   : []),
    { label: 'Reference', value: receipt.reference },
  ];

  return (
    <ResultSheetLayout>
      <br />
      <br />
      <br />
      <Div
        width={'800px'}
        margin="auto"
        p={6}
        boxShadow="md"
        borderRadius="md"
        borderWidth="1px"
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
              {receipt.title}
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
            scroll={true}
            headers={feePaymentTableHeaders}
            data={receipt.fee_payments!}
            keyExtractor={(row) => row.id}
            hideSearchField={true}
            tableProps={{ className: 'result-table' }}
          />
          <br />
        </div>
        <Text textAlign="right" fontSize="xl">
          <strong>Total Amount:</strong>{' '}
          {formatAsCurrency(receipt.total_amount)}
        </Text>
        <Divider my={4} />
        <Text textAlign="center">Thank you for your payment!</Text>
      </Div>
    </ResultSheetLayout>
  );
}
