import React from 'react';
import { Funding } from '@/types/models';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DashboardLayout from '@/layout/dashboard-layout';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  fundings: PaginationResponse<Funding>;
}

function NumberFormatter(number: number) {
  return new Intl.NumberFormat().format(number);
}

export default function ListInstitutionFundings({ fundings }: Props) {
  const { instRoute } = useInstitutionRoute();

  const headers: ServerPaginatedTableHeader<Funding>[] = [
    {
      label: 'Amount Funded',
      value: 'amount',
      render: (row) => '₦ ' + NumberFormatter(row.amount),
    },
    {
      label: 'Previous Balance',
      value: 'previous_balance',
      render: (row) => '₦ ' + NumberFormatter(row.previous_balance),
    },
    {
      label: 'New Balance',
      value: 'new_balance',
      render: (row) => '₦ ' + NumberFormatter(row.new_balance),
    },
    {
      label: 'Reference',
      value: 'reference',
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Deposits"
          rightElement={
            <LinkButton
              href={instRoute('fundings.create')}
              title={'Add Fund'}
            />
          }
        />

        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={fundings.data}
            keyExtractor={(row) => row.id}
            paginator={fundings}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
