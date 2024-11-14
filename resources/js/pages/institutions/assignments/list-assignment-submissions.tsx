import React from 'react';
import { AssignmentSubmission } from '@/types/models';
import { Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import DateTimeDisplay from '@/components/date-time-display';
import { dateTimeFormat } from '@/util/util';

interface Props {
  assignmentSubmissions: PaginationResponse<AssignmentSubmission>;
}

export default function ListEvents({ assignmentSubmissions }: Props) {
  const { instRoute } = useInstitutionRoute();

  const headers: ServerPaginatedTableHeader<AssignmentSubmission>[] = [
    {
      label: 'Class',
      value: 'assignment.classification.title',
    },
    {
      label: 'Subject',
      value: 'assignment.course.title',
    },
    {
      label: 'Student',
      value: '',
      render: (row) => (
        <Text>
          {row.student?.user?.first_name + ' ' + row.student?.user?.last_name}
        </Text>
      ),
    },
    {
      label: 'Submitted On',
      value: 'created_at',
      render: (row) => (
        <DateTimeDisplay
          dateTime={row.created_at}
          dateTimeformat={dateTimeFormat}
        />
      ),
    },
    {
      label: 'Score',
      value: 'score',
      render: (row) => (
        <Text>{row.score + ' / ' + row.assignment.max_score}</Text>
      ),
    },
    {
      label: 'Action',
      render: (row: AssignmentSubmission) => (
        <LinkButton
          href={instRoute('assignment-submissions.show', [row.id])}
          variant={'link'}
          title="View"
        />
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Submitted Assignments" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={assignmentSubmissions.data}
            keyExtractor={(row) => row.id}
            paginator={assignmentSubmissions}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
