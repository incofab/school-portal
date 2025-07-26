import React from 'react';
import { Payroll } from '@/types/models';
import {
  Button,
  Divider,
  Grid,
  GridItem,
  HStack,
  Icon,
  Image,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { formatAsCurrency, ucFirst } from '@/util/util';
import { PrinterIcon } from '@heroicons/react/24/solid';
import Slab, { SlabBody } from '@/components/slab';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import DateTimeDisplay from '@/components/date-time-display';
import DataTable from '@/components/data-table';
import ImagePaths from '@/util/images';

interface Props {
  payroll: Payroll;
}

const PayslipRow = ({
  label,
  value,
}: {
  label: string;
  value: string | number;
}) => (
  <HStack justifyContent="space-between" w="full">
    <Text fontSize="sm">{label}</Text>
    <Text fontSize="sm" fontWeight="semibold">
      {value}
    </Text>
  </HStack>
);

export default function ShowPayroll({ payroll }: Props) {
  const payrollSummary = payroll.payroll_summary!;
  const { currentInstitution } = useSharedProps();
  const bgColor = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');

  const handlePrint = () => {
    window.print();
  };

  return (
    <DashboardLayout>
      <style>
        {`
          @media print {
            .print-hidden {
              display: none;
            }
            body {
              -webkit-print-color-adjust: exact;
            }
            body * {
                visibility: hidden;
            }
            @page {
              size: A4;
              margin: 0;
            }
            .payslip-container, .payslip-container * {
                visibility: visible;
            }
            .payslip-container {
              box-shadow: none;
              border: none;
              margin: 0;
              padding: 25px 25px;

              position: absolute;
              left: 0;
              top: 0;
              width: 100%;
            }
          }
        `}
      </style>
      <Slab>
        <SlabBody>
          <HStack justifyContent={'end'} className="print-hidden">
            <Button
              colorScheme="brand"
              leftIcon={<Icon as={PrinterIcon} />}
              onClick={handlePrint}
            >
              Print Payslip
            </Button>
          </HStack>
          <Div
            maxW="800px"
            mx="auto"
            mt={4}
            p={8}
            bg={bgColor}
            border="1px"
            borderColor={borderColor}
            borderRadius="md"
            boxShadow="lg"
            className="payslip-container"
          >
            {/* Header */}
            <HStack>
              <VStack
                spacing={2}
                alignItems="start"
                mb={3}
                align={'stretch'}
                w={'full'}
              >
                <Text fontSize="2xl" fontWeight="bold">
                  {currentInstitution.name}
                </Text>
                <Text fontSize="sm">{currentInstitution.address}</Text>
                <Text fontSize="lg" fontWeight="bold">
                  Payslip for {ucFirst(payrollSummary.month)},{' '}
                  {payrollSummary.year}
                </Text>
              </VStack>
              <Image
                src={currentInstitution.photo ?? ImagePaths.default_school_logo}
                alt={`${currentInstitution.name} logo`}
                boxSize="80px"
                objectFit="contain"
              />
            </HStack>

            <Divider my={6} />

            {/* Employee Details */}
            <Grid
              templateColumns={{ base: '1fr', md: 'repeat(2, 1fr)' }}
              gap={6}
              mb={6}
            >
              <GridItem>
                <Text fontWeight="bold">Employee Name:</Text>
                <Text>{payroll.institution_user!.user!.full_name}</Text>
              </GridItem>
              <GridItem>
                <Text fontWeight="bold">Designation:</Text>
                <Text>{ucFirst(payroll.institution_user!.role)}</Text>
              </GridItem>
              <GridItem>
                <Text fontWeight="bold">Date:</Text>
                <Text>
                  <DateTimeDisplay dateTime={payroll.created_at} />
                </Text>
              </GridItem>
            </Grid>

            <Divider my={6} />

            {/* Earnings and Deductions */}
            <Grid
              templateColumns={{ base: '1fr', md: 'repeat(2, 1fr)' }}
              gap={8}
            >
              {/* Earnings */}
              <GridItem>
                <Text fontSize="lg" fontWeight="bold" mb={1}>
                  Salary Components
                </Text>
                <DataTable
                  hideSearchField={true}
                  data={payroll.meta!.salaries}
                  headers={[
                    { label: 'Title', value: 'title' },
                    { label: 'Type', value: 'type' },
                    {
                      label: 'Amount',
                      render: (row: any) => (
                        <Text fontWeight={'semibold'}>
                          {formatAsCurrency(row.amount)}
                        </Text>
                      ),
                    },
                  ]}
                  keyExtractor={(row: any) => row.title}
                />
              </GridItem>
              {/* Bonuses/Deductions */}
              <GridItem>
                <Text fontSize="lg" fontWeight="bold" mb={1}>
                  Bonuses/Deductions
                </Text>
                <DataTable
                  hideSearchField={true}
                  data={payroll.meta!.adjustments}
                  headers={[
                    { label: 'Title', value: 'title' },
                    { label: 'Type', value: 'type' },
                    {
                      label: 'Amount',
                      render: (row: any) => (
                        <Text fontWeight={'semibold'}>
                          {formatAsCurrency(row.amount)}
                        </Text>
                      ),
                    },
                  ]}
                  keyExtractor={(row: any) => row.title}
                />
              </GridItem>
            </Grid>

            <Divider my={6} />

            {/* Net Salary */}
            <PayslipRow
              label={'Bonuses'}
              value={formatAsCurrency(payroll.total_bonuses)}
            />
            <PayslipRow
              label={'Deductions'}
              value={formatAsCurrency(payroll.total_deductions)}
            />
            {/* <PayslipRow label={'Tax'} value={formatAsCurrency(payroll.tax)} /> */}
            <PayslipRow
              label={'Gross Salary'}
              value={formatAsCurrency(payroll.gross_salary)}
            />
            <HStack justifyContent="flex-end" mt={6}>
              <Text fontSize="xl" fontWeight="bold">
                Net Salary:
              </Text>
              <Text fontSize="xl" fontWeight="bold" color="brand.500">
                {formatAsCurrency(payroll.net_salary)}
              </Text>
            </HStack>

            {/* Footer */}
            <VStack mt={12} spacing={6}>
              <HStack w="full" justifyContent="space-between">
                <VStack alignItems="center">
                  <Divider w="150px" borderColor="gray.500" />
                  <Text fontSize="sm">Employee's Signature</Text>
                </VStack>
                <VStack alignItems="center">
                  <Divider w="150px" borderColor="gray.500" />
                  <Text fontSize="sm">Authorized Signature</Text>
                </VStack>
              </HStack>
            </VStack>
          </Div>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
