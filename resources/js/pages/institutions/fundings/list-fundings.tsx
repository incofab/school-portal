import React from 'react';
import { Funding } from '@/types/models';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DashboardLayout from '@/layout/dashboard-layout';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { formatAsCurrency } from '@/util/util';

interface Props {
  fundings: PaginationResponse<Funding>;
}

export default function ListInstitutionFundings({ fundings }: Props) {
  const { instRoute } = useInstitutionRoute();

  const headers: ServerPaginatedTableHeader<Funding>[] = [
    {
      label: 'Amount Funded',
      value: 'amount',
      render: (row) => formatAsCurrency(row.amount),
    },
    {
      label: 'Previous Balance',
      value: 'previous_balance',
      render: (row) => formatAsCurrency(row.previous_balance),
    },
    {
      label: 'New Balance',
      value: 'new_balance',
      render: (row) => formatAsCurrency(row.new_balance),
    },
    {
      label: 'Remark',
      value: 'remark',
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
