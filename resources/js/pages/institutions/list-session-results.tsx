import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { SessionResult, Student } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import useInstitutionRoute from '@/hooks/use-institution-route';
import SessionResultsTableFilters from '@/components/table-filters/session-result-table-filters';
import { LinkButton } from '@/components/buttons';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';

interface Props {
  sessionResults: PaginationResponse<SessionResult>;
  student?: Student;
}

export default function ListSessionResults({ sessionResults, student }: Props) {
  const sessionResultFilterToggle = useModalToggle();
  const { instRoute } = useInstitutionRoute();

  const headers: ServerPaginatedTableHeader<SessionResult>[] = [
    {
      label: 'User',
      value: 'student.user.full_name',
      render: (row) => <DisplayUserFullname user={row.student?.user} />,
    },
    {
      label: 'Class',
      value: 'classification.title',
    },
    {
      label: 'Session',
      value: 'academic_session.title',
    },
    {
      label: 'Average',
      value: 'average',
    },
    {
      label: 'Total',
      value: 'result',
    },
    {
      label: 'Grade',
      value: 'grade',
    },
    {
      label: 'Action',
      render: (row) => (
        <LinkButton
          href={instRoute('session-results.show', [row])}
          title="Result Sheet"
          variant={'link'}
        />
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`Session Results ${
            student ? `- ${student!.user?.full_name}` : ''
          }`}
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={sessionResults.data}
            keyExtractor={(row) => row.id}
            paginator={sessionResults}
            validFilters={['classification', 'academicSession', 'student']}
            onFilterButtonClick={sessionResultFilterToggle.open}
          />
        </SlabBody>
        <SessionResultsTableFilters {...sessionResultFilterToggle.props} />
      </Slab>
    </DashboardLayout>
  );
}
