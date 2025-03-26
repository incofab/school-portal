import { ResultPublication } from '@/types/models';
import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { dateTimeFormat, formatAsCurrency } from '@/util/util';
import DateTimeDisplay from '@/components/date-time-display';
import { PaginationResponse } from '@/types/types';
import { LinkButton } from '@/components/buttons';
import startCase from 'lodash/startCase';

interface Props {
  resultPublications: PaginationResponse<ResultPublication>;
}

export default function ListResultPublications({ resultPublications }: Props) {
  const { instRoute } = useInstitutionRoute();

  const headers: ServerPaginatedTableHeader<ResultPublication>[] = [
    {
      label: 'Staff',
      render: (row) => `${row.staff?.first_name} ${row.staff?.last_name}`,
    },
    {
      label: 'Session',
      value: 'academic_session.title',
    },
    {
      label: 'Term',
      render: (row) => startCase(row.term),
    },
    {
      label: 'No Of Results',
      value: 'num_of_results',
    },
    {
      label: 'Structure',
      render: (row) => startCase(row.payment_structure),
    },
    {
      label: 'Amount',
      render: (row) =>
        row.transaction ? formatAsCurrency(row.transaction.amount) : null,
    },
    {
      label: 'Date',
      render: (row) => (
        <small>
          <DateTimeDisplay
            dateTime={row.created_at}
            dateTimeformat={dateTimeFormat}
          />
        </small>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Result Publications"
          rightElement={
            <LinkButton
              href={instRoute('result-publications.create')}
              title={'Publish Result'}
            />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={resultPublications.data}
            keyExtractor={(row) => row.id}
            paginator={resultPublications}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
