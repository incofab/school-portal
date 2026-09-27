import React from 'react';
import {
  Alert,
  AlertIcon,
  Badge,
  Box,
  FormControl,
  FormLabel,
  HStack,
  Icon,
  SimpleGrid,
  Stack,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  UserGroupIcon,
  XCircleIcon,
} from '@heroicons/react/24/outline';
import startCase from 'lodash/startCase';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FeeSelect from '@/components/selectors/fee-select';
import ClassificationSelect from '@/components/selectors/classification-select';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useQueryString from '@/hooks/use-query-string';
import {
  Classification,
  Fee,
  FeeCollectionStatus,
  FeeCollectionStudent,
  FeeCollectionSummary,
} from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { formatAsCurrency } from '@/util/util';
import { LinkButton } from '@/components/buttons';

interface Overview {
  summary: FeeCollectionSummary;
  students: PaginationResponse<FeeCollectionStudent>;
}

interface Props {
  fees: Fee[];
  classifications: Classification[];
  selectedFee?: Fee | null;
  selectedClassification?: Classification | null;
  overview?: Overview | null;
}

interface MetricCardProps {
  label: string;
  description: string;
  count: number;
  icon: React.ElementType;
  colorScheme: string;
  isActive: boolean;
  onClick?(): void;
}

function MetricCard({
  label,
  description,
  count,
  icon,
  colorScheme,
  isActive,
  onClick,
}: MetricCardProps) {
  const defaultBorder = useColorModeValue('gray.200', 'gray.600');
  const activeBackground = useColorModeValue(
    `${colorScheme}.50`,
    `${colorScheme}.900`
  );

  return (
    <Box
      as={onClick ? 'button' : undefined}
      type={onClick ? 'button' : undefined}
      onClick={onClick}
      aria-pressed={onClick ? isActive : undefined}
      textAlign="left"
      borderWidth={1}
      borderColor={isActive ? `${colorScheme}.400` : defaultBorder}
      background={isActive ? activeBackground : undefined}
      rounded="lg"
      p={4}
      transition="border-color 0.15s ease"
      _hover={{ borderColor: `${colorScheme}.400` }}
      _focusVisible={{
        outline: '2px solid',
        outlineColor: `${colorScheme}.400`,
        outlineOffset: '2px',
      }}
    >
      <HStack justify="space-between" align="start">
        <VStack align="start" spacing={1}>
          <Text fontSize="sm" fontWeight="semibold">
            {label}
          </Text>
          <Text fontSize="2xl" fontWeight="bold" lineHeight="1">
            {count}
          </Text>
          <Text fontSize="xs" color="gray.500">
            {description}
          </Text>
        </VStack>
        <Icon as={icon} boxSize={6} color={`${colorScheme}.500`} />
      </HStack>
    </Box>
  );
}

function StatusBadge({ status }: { status: FeeCollectionStatus }) {
  const colorScheme = {
    paid: 'green',
    partial: 'orange',
    unpaid: 'red',
  }[status];

  return (
    <Badge colorScheme={colorScheme} textTransform="capitalize">
      {status}
    </Badge>
  );
}

export default function FeeCollectionOverview({
  fees,
  classifications,
  selectedFee,
  selectedClassification,
  overview,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { params, updateQueryString } = useQueryString();
  const activeStatus = params.status as FeeCollectionStatus | undefined;

  function updateFilters(filters: Record<string, string>) {
    updateQueryString(
      { ...filters, page: '' },
      { preserveState: true, preserveScroll: true }
    );
  }

  function selectFee(option: any) {
    updateFilters({
      fee: option?.value?.toString() ?? '',
      classification: '',
      status: '',
    });
  }

  function selectClassification(option: any) {
    updateFilters({ classification: option?.value?.toString() ?? '' });
  }

  const headers: ServerPaginatedTableHeader<FeeCollectionStudent>[] = [
    {
      label: 'Student',
      render: (row) => (
        <VStack align="start" spacing={0} minW="180px">
          <Text fontWeight="semibold">{row.student.user?.full_name}</Text>
          <Text fontSize="xs" color="gray.500">
            {row.student.full_code}
          </Text>
        </VStack>
      ),
    },
    {
      label: 'Class',
      render: (row) => row.student.classification?.title ?? 'Unassigned',
    },
    {
      label: 'Expected',
      render: (row) => formatAsCurrency(row.amount_expected),
    },
    {
      label: 'Paid',
      render: (row) => formatAsCurrency(row.amount_paid),
    },
    {
      label: 'Outstanding',
      render: (row) => formatAsCurrency(row.amount_remaining),
    },
    {
      label: 'Status',
      render: (row) => <StatusBadge status={row.status} />,
    },
  ];

  const metrics = overview
    ? [
        {
          key: 'paid' as const,
          label: 'Paid in full',
          description: 'No balance remaining',
          count: overview.summary.paid,
          icon: CheckCircleIcon,
          colorScheme: 'green',
        },
        {
          key: 'partial' as const,
          label: 'Partial payment',
          description: 'Students with a balance',
          count: overview.summary.partial,
          icon: ExclamationTriangleIcon,
          colorScheme: 'orange',
        },
        {
          key: 'unpaid' as const,
          label: 'Unpaid',
          description: 'No payment recorded',
          count: overview.summary.unpaid,
          icon: XCircleIcon,
          colorScheme: 'red',
        },
        {
          key: undefined,
          label: 'Expected students',
          description: 'Eligible for this fee',
          count: overview.summary.total_students,
          icon: UserGroupIcon,
          colorScheme: 'brand',
        },
      ]
    : [];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Fee Collection Overview"
          rightElement={
            <LinkButton
              title="Fee Payments"
              href={instRoute('fee-payments.index')}
              variant="outline"
            />
          }
        />
        <SlabBody>
          <Stack spacing={5}>
            <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
              <FormControl>
                <FormLabel mb={1}>Fee</FormLabel>
                <FeeSelect
                  fees={fees}
                  selectValue={selectedFee?.id ?? ''}
                  onChange={selectFee}
                  isClearable={true}
                  placeholder="Select a fee to review"
                />
              </FormControl>
              <FormControl>
                <FormLabel mb={1}>Class</FormLabel>
                <ClassificationSelect
                  classifications={classifications}
                  selectValue={selectedClassification?.id ?? ''}
                  onChange={selectClassification}
                  isClearable={true}
                  placeholder="All eligible classes"
                />
              </FormControl>
            </SimpleGrid>

            {!selectedFee || !overview ? (
              <Alert status="info" rounded="md">
                <AlertIcon />
                Select a fee to see who has paid, who has an outstanding
                balance, and who has not paid.
              </Alert>
            ) : (
              <Stack spacing={5}>
                <Box>
                  <Text fontSize="lg" fontWeight="semibold">
                    {selectedFee.title}
                  </Text>
                  <Text fontSize="sm" color="gray.500">
                    {formatAsCurrency(selectedFee.amount)} per student
                    {selectedFee.academic_session?.title
                      ? ` · ${selectedFee.academic_session.title}`
                      : ''}
                    {selectedFee.term
                      ? ` · ${startCase(selectedFee.term)} term`
                      : ''}
                  </Text>
                </Box>

                <SimpleGrid columns={{ base: 1, sm: 2, xl: 4 }} spacing={3}>
                  {metrics.map((metric) => (
                    <MetricCard
                      key={metric.label}
                      label={metric.label}
                      description={metric.description}
                      count={metric.count}
                      icon={metric.icon}
                      colorScheme={metric.colorScheme}
                      isActive={metric.key === activeStatus}
                      onClick={
                        metric.key
                          ? () =>
                              updateFilters({
                                status:
                                  metric.key === activeStatus ? '' : metric.key,
                              })
                          : undefined
                      }
                    />
                  ))}
                </SimpleGrid>

                <HStack
                  spacing={{ base: 3, md: 6 }}
                  flexWrap="wrap"
                  fontSize="sm"
                  color="gray.600"
                >
                  <Text>
                    Expected:{' '}
                    {formatAsCurrency(overview.summary.amount_expected)}
                  </Text>
                  <Text>
                    Collected: {formatAsCurrency(overview.summary.amount_paid)}
                  </Text>
                  <Text>
                    Outstanding:{' '}
                    {formatAsCurrency(overview.summary.amount_remaining)}
                  </Text>
                </HStack>

                <Box>
                  <Text fontSize="md" fontWeight="semibold" mb={3}>
                    {activeStatus
                      ? `${startCase(activeStatus)} students`
                      : 'Student payment status'}
                  </Text>
                  {overview.students.total === 0 ? (
                    <Alert status="info" rounded="md">
                      <AlertIcon />
                      No students match the selected fee, class, or payment
                      status.
                    </Alert>
                  ) : (
                    <ServerPaginatedTable
                      scroll={true}
                      headers={headers}
                      data={overview.students.data}
                      keyExtractor={(row) => row.student.id}
                      paginator={overview.students}
                      hideSearchField={true}
                    />
                  )}
                </Box>
              </Stack>
            )}
          </Stack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
