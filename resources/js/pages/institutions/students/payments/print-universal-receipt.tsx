import React from 'react';
import {
  AcademicSession,
  FeePayment,
  Receipt,
  Student,
  User,
} from '@/types/models';
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
import { LabelText } from '@/components/result-helper-components';
import ImagePaths from '@/util/images';
import ResultSheetLayout from '../../result-sheets/result-sheet-layout';
import startCase from 'lodash/startCase';
import DateTimeDisplay from '@/components/date-time-display';
import { FeeItem, TermType } from '@/types/types';

interface Props {
  receipts: Receipt[];
  student: Student;
  user: User;
  term: TermType;
  academic_session: AcademicSession;
}

export default function PrintReceiptPage({
  receipts,
  student,
  user,
  term,
  academic_session,
}: Props) {
  const { currentInstitution, stamp } = useSharedProps();

  const feeItemTableHeaders: TableHeader<FeeItem>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Amount',
      value: 'amount',
    },
  ];

  const feePaymentTableHeaders: TableHeader<FeePayment>[] = [
    {
      label: 'Paid/Confirmed By',
      value: 'payable.full_name',
      render: (row) =>
        row.payable?.full_name ?? row.confirmed_by?.full_name ?? '',
    },
    {
      label: 'Amount Paid',
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
    { label: 'Student Name', value: user?.full_name },
    { label: 'Class', value: student.classification?.title },
    ...(academic_session || term
      ? [
          {
            label: 'Session',
            value: `${term ? `${startCase(term)} Term, ` : ''} ${
              academic_session?.title
            }`,
          },
        ]
      : []),
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
              Universal Receipt
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
          {receipts.map((receipt, i) => {
            const feeItems = receipt.fee?.fee_items || [];
            const leftItems = feeItems.filter((_, index) => index % 2 === 0); // 0-based: 0, 2, 4, ...
            const rightItems = feeItems.filter((_, index) => index % 2 !== 0); // 1, 3, 5, ...

            return (
              <Div key={receipt.id}>
                <Text
                  textAlign="center"
                  fontSize="sm"
                  mb={0}
                  mt={6}
                  fontWeight={'bold'}
                >
                  {receipt.fee?.title}
                  <Text color={'red'}>
                    {receipt.amount_remaining > 0
                      ? `(${receipt.amount_remaining} Unpaid)`
                      : ''}
                  </Text>
                </Text>

                {receipt.fee_payments!.length > 0 && (
                  <DataTable
                    scroll={true}
                    headers={feePaymentTableHeaders}
                    data={receipt.fee_payments!}
                    keyExtractor={(row) => row.id}
                    hideSearchField={true}
                    tableProps={{
                      className: 'result-table',
                      variant: 'striped',
                    }}
                  />
                )}

                <HStack gap={3} key={i} alignItems={'top'}>
                  <Div width={'50%'} height={'100%'}>
                    {leftItems.length > 0 && (
                      <DataTable
                        scroll={true}
                        headers={feeItemTableHeaders}
                        data={leftItems}
                        keyExtractor={(row) => row.title}
                        hideSearchField={true}
                        tableProps={{ className: 'result-table' }}
                      />
                    )}
                  </Div>

                  <Div width={'50%'} height={'100%'}>
                    {rightItems.length > 0 && (
                      <DataTable
                        scroll={true}
                        headers={feeItemTableHeaders}
                        data={rightItems}
                        keyExtractor={(row) => row.title}
                        hideSearchField={true}
                        tableProps={{ className: 'result-table' }}
                      />
                    )}
                  </Div>
                </HStack>
              </Div>
            );
          })}
        </div>
        <Divider my={5} />
        {receipts.length > 0 ? (
          <Text textAlign="center">Thank you for your payment!</Text>
        ) : (
          <Div textAlign={'center'} fontSize={'lg'}>
            No receipts found for {user.full_name} in{' '}
            {term ? `${startCase(term)} Term,` : ''} {academic_session?.title}{' '}
            Session
          </Div>
        )}
      </Div>
    </ResultSheetLayout>
  );
}
