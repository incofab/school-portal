import React from 'react';
import { Badge, Link } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { ManualPayment } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import startCase from 'lodash/startCase';
import { formatAsCurrency } from '@/util/util';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  manualPayments: PaginationResponse<ManualPayment>;
}

function statusColor(status: string) {
  if (status === 'confirmed') return 'green';
  if (status === 'cancelled') return 'red';
  return 'yellow';
}

export default function ManualPaymentHistory({ manualPayments }: Props) {
  const { instRoute } = useInstitutionRoute();
  const headers: ServerPaginatedTableHeader<ManualPayment>[] = [
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
      value: 'reference',
    },
    {
      label: 'Purpose',
      render: (row) => startCase(row.purpose),
    },
    {
      label: 'Amount',
      render: (row) => formatAsCurrency(row.amount),
    },
    {
      label: 'Bank',
      render: (row) =>
        row.bank_account
          ? `${row.bank_account.bank_name} - ${row.bank_account.account_number}`
          : '',
    },
    {
      label: 'Proof',
      render: (row) =>
        row.proof_url ? (
          <Link href={row.proof_url} target="_blank" color="brand.500">
            View
          </Link>
        ) : (
          ''
        ),
    },
    {
      label: 'Remark',
      value: 'review_note',
    },
    {
      label: 'Action',
      render: (row) =>
        row.status === 'pending' ? (
          <Link
            href={instRoute('manual-payments.show', [row.reference])}
            color="brand.500"
          >
            Continue
          </Link>
        ) : (
          ''
        ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Manual Payment History" />
        <SlabBody>
          <ServerPaginatedTable
            scroll
            headers={headers}
            data={manualPayments.data}
            keyExtractor={(row) => row.id}
            paginator={manualPayments}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
