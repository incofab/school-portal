import React, { useMemo } from 'react';
import { Badge, Box, Button, Text, VStack } from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import DashboardLayout from '@/layout/dashboard-layout';
import CenteredLayout from '@/components/centered-layout';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import route from '@/util/route';
import { formatAsCurrency } from '@/util/util';
import { Institution, PaymentReference } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import startCase from 'lodash/startCase';
import useModalToggle from '@/hooks/use-modal-toggle';
import PaymentReferenceTableFilters from '@/components/table-filters/payment-reference-table-filters';
import { dateRangeFilterQueryKeys } from '@/components/table-filters/date-range-filter';

interface Props {
  paymentReferences: PaginationResponse<
    PaymentReference & { institution?: Institution }
  >;
  context: 'user' | 'institution' | 'manager';
}

function statusColor(status: string) {
  if (status === 'confirmed') return 'green';
  if (status === 'cancelled') return 'red';
  return 'yellow';
}

function detailText(paymentReference: PaymentReference) {
  const values = [
    paymentReference.payer_name
      ? `Payer: ${paymentReference.payer_name}`
      : null,
    paymentReference.purpose_details
      ? `For: ${paymentReference.purpose_details}`
      : null,
    ...Object.entries(paymentReference.meta_summary ?? {}).map(
      ([key, value]) => `${startCase(key)}: ${value ?? 'N/A'}`
    ),
  ].filter(Boolean);

  return values.length ? values.join(' | ') : 'No extra details recorded';
}

export default function ListPaymentAttempts({
  paymentReferences,
  context,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const verifyForm = useWebForm({ id: 0 });
  const paymentReferenceFilterToggle = useModalToggle();
  const isInstitutionContext = context === 'institution';
  const isManagerContext = context === 'manager';

  const verifyRoute = (paymentReference: PaymentReference) =>
    isInstitutionContext
      ? instRoute('payment-attempts.verify', [paymentReference.reference])
      : route('payment-attempts.verify', [paymentReference.reference]);

  async function verifyPayment(paymentReference: PaymentReference) {
    verifyForm.setValue('id', paymentReference.id);
    const res = await verifyForm.submit((data, web) =>
      web.post(verifyRoute(paymentReference), data, {
        headers: {
          'Idempotency-Key': `payment-history:${paymentReference.reference}`,
        },
      })
    );

    handleResponseToast(res);
    Inertia.reload({ only: ['paymentReferences'] });
  }

  const headers = useMemo<ServerPaginatedTableHeader<PaymentReference>[]>(
    () => [
      ...(isManagerContext
        ? [
            {
              label: 'Institution',
              render: (row: any) => row.institution?.name,
            },
          ]
        : []),
      {
        label: 'Status',
        render: (row) => (
          <Badge colorScheme={statusColor(row.status)}>
            {startCase(row.status)}
          </Badge>
        ),
      },
      {
        label: 'Reference',
        render: (row) => (
          <Box maxW="240px">
            <Text fontWeight="semibold" noOfLines={1}>
              {row.reference}
            </Text>
            <Text color="gray.500" fontSize="xs" noOfLines={2}>
              {detailText(row)}
            </Text>
          </Box>
        ),
      },
      {
        label: 'Purpose',
        render: (row) => startCase(row.purpose),
      },
      {
        label: 'Amount',
        render: (row) => (
          <Text fontWeight="semibold">{formatAsCurrency(row.amount)}</Text>
        ),
      },
      {
        label: 'Provider',
        render: (row) => startCase(row.merchant),
      },
      {
        label: 'Date',
        render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
      },
      {
        label: 'Processed',
        render: (row) =>
          row.processed_at ? (
            <DateTimeDisplay dateTime={row.processed_at} />
          ) : (
            <Text color="gray.500">Not processed</Text>
          ),
      },
      {
        label: 'Action',
        render: (row) =>
          row.can_verify ? (
            <Button
              size="sm"
              colorScheme="brand"
              variant="outline"
              isLoading={verifyForm.processing && verifyForm.data.id === row.id}
              onClick={() => verifyPayment(row)}
            >
              Verify Payment
            </Button>
          ) : (
            <Text color="gray.500" fontSize="sm">
              No action
            </Text>
          ),
      },
    ],
    [verifyForm.processing, isInstitutionContext, isManagerContext]
  );

  const content = (
    <Slab>
      <SlabHeading
        title={
          isInstitutionContext || isManagerContext
            ? 'Payment Attempts'
            : 'My Payment Attempts'
        }
      />
      <SlabBody>
        <ServerPaginatedTable
          scroll
          hideSearchField
          headers={headers}
          data={paymentReferences.data}
          keyExtractor={(row) => row.id}
          paginator={paymentReferences}
          validFilters={[
            ...(isManagerContext ? ['institution_id'] : []),
            'search',
            'reference',
            'status',
            'merchant',
            'purpose',
            ...dateRangeFilterQueryKeys('created_at'),
          ]}
          onFilterButtonClick={paymentReferenceFilterToggle.open}
        />
        {paymentReferences.data.length === 0 && (
          <VStack
            borderWidth="1px"
            borderColor="gray.200"
            borderRadius="md"
            py={10}
            px={4}
            spacing={1}
          >
            <Text fontWeight="semibold">No payment attempts found</Text>
            <Text color="gray.500" textAlign="center" fontSize="sm">
              Payment attempts will appear here after a checkout or wallet
              payment is initialized.
            </Text>
          </VStack>
        )}
      </SlabBody>
      <PaymentReferenceTableFilters
        {...paymentReferenceFilterToggle.props}
        showInstitution={isManagerContext}
      />
    </Slab>
  );

  if (isManagerContext) {
    return <ManagerDashboardLayout>{content}</ManagerDashboardLayout>;
  }

  return isInstitutionContext ? (
    <DashboardLayout>{content}</DashboardLayout>
  ) : (
    <CenteredLayout boxProps={{ maxW: '7xl' }}>{content}</CenteredLayout>
  );
}
