import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { TermResult } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { Text } from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import TermResultsTableFilters from '@/components/table-filters/term-result-table-filters';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  termResults: PaginationResponse<TermResult>;
}

export default function ListTermResults({ termResults }: Props) {
  const termResultFilterToggle = useModalToggle();
  const { instRoute } = useInstitutionRoute();

  const headers: ServerPaginatedTableHeader<TermResult>[] = [
    {
      label: 'User',
      value: 'student.user.full_name',
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
      label: 'Term',
      value: 'term',
      render: (row) => (
        <Text>
          {startCase(row.term)} {row.for_mid_term ? 'Mid-' : ''}Term
        </Text>
      ),
    },
    {
      label: 'Position',
      value: 'position',
    },
    {
      label: 'Total Score',
      value: 'total_score',
    },
    {
      label: 'Average',
      value: 'average',
    },
    {
      label: 'Remark',
      value: 'remark',
    },
    {
      label: 'Action',
      render: (row) => (
        <LinkButton
          href={instRoute('students.term-result-detail', [
            row.student_id,
            row.classification_id,
            row.academic_session_id,
            row.term,
            row.for_mid_term ? 1 : 0,
          ])}
          title="Result Detail"
        />
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Term Results" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={termResults.data}
            keyExtractor={(row) => row.id}
            paginator={termResults}
            validFilters={[
              'classification',
              'academicSession',
              'student',
              'term',
            ]}
            onFilterButtonClick={termResultFilterToggle.open}
          />
        </SlabBody>
        <TermResultsTableFilters {...termResultFilterToggle.props} />
      </Slab>
    </DashboardLayout>
  );
}
