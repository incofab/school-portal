import React from 'react';
import { Badge, HStack, Link } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { ManualPayment } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { BrandButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import startCase from 'lodash/startCase';
import { formatAsCurrency } from '@/util/util';

interface Props {
  manualPayments: PaginationResponse<ManualPayment>;
}

function statusColor(status: string) {
  if (status === 'confirmed') return 'green';
  if (status === 'cancelled') return 'red';
  return 'yellow';
}

export default function ListManualPayments({ manualPayments }: Props) {
  const { instRoute } = useInstitutionRoute();
  const form = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function confirmPayment(manualPayment: ManualPayment) {
    if (!confirm('Are you sure you want to confirm this payment?')) {
      return;
    }
    const res = await form.submit((data, web) =>
      web.post(instRoute('manual-payments.confirm', [manualPayment.id]), data)
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['manualPayments'] });
  }

  async function rejectPayment(manualPayment: ManualPayment) {
    if (!confirm('Are you sure you want to reject this payment?')) {
      return;
    }
    const reviewNote =
      window.prompt('Reason for rejecting this payment?') ?? '';
    const res = await form.submit((data, web) =>
      web.post(instRoute('manual-payments.reject', [manualPayment.id]), {
        review_note: reviewNote,
      })
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['manualPayments'] });
  }

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
      label: 'Paid By',
      render: (row) =>
        row.payable?.full_name ??
        row.user?.full_name ??
        row.depositor_name ??
        '',
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
    // {
    //   label: 'Proof',
    //   render: (row) =>
    //     row.proof_url ? (
    //       <Link href={row.proof_url} target="_blank" color="brand.500">
    //         View
    //       </Link>
    //     ) : (
    //       ''
    //     ),
    // },
    {
      label: 'Reviewed By',
      render: (row) =>
        row.confirmed_by?.full_name ?? row.rejected_by?.full_name ?? '',
    },
    {
      label: 'Action',
      render: (row) =>
        row.status === 'pending' ? (
          <HStack>
            <Link
              href={instRoute('manual-payments.show', [row.reference])}
              colorScheme={'brand'}
            >
              View
            </Link>
            <BrandButton
              title="Confirm"
              onClick={() => confirmPayment(row)}
              isLoading={form.processing}
            />
            <BrandButton
              title="Reject"
              colorScheme="red"
              onClick={() => rejectPayment(row)}
              isLoading={form.processing}
            />
          </HStack>
        ) : null,
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Manual Payments" />
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
