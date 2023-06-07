import React from 'react';
import { Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { CourseResultInfo } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { PaginationResponse } from '@/types/types';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import startCase from 'lodash/startCase';
import route from '@/util/route';
import useSharedProps from '@/hooks/use-shared-props';
import CourseResultInfoTableFilters from '@/components/table-filters/course-result-info-table-filters';
import useModalToggle from '@/hooks/use-modal-toggle';

interface Props {
  courseResultInfo: PaginationResponse<CourseResultInfo>;
}

export default function ListCourseResultInfo({ courseResultInfo }: Props) {
  const { currentInstitution } = useSharedProps();
  const courseResultInfoFilterToggle = useModalToggle();

  const headers: ServerPaginatedTableHeader<CourseResultInfo>[] = [
    {
      label: 'Course',
      value: 'course.title',
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
      render: (row) => <Text>{startCase(row.term)}</Text>,
    },
    {
      label: 'Num of Students',
      value: 'num_of_students',
    },
    {
      label: 'Total Score',
      value: 'total_score',
    },
    {
      label: 'Max Obtainable Score',
      value: 'max_obtainable_score',
    },
    {
      label: 'Student Max Score',
      value: 'max_score',
    },
    {
      label: 'Student Min Score',
      value: 'min_score',
    },
    {
      label: 'Average',
      value: 'average',
    },
    {
      label: 'Action',
      render: (row) => (
        <LinkButton
          href={route('institutions.course-results.index', {
            institution: currentInstitution.uuid,
            classification: row.classification_id,
            course: row.course_id,
          })}
          title="Student Results"
        />
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Recorded Result Detail" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={courseResultInfo.data}
            keyExtractor={(row) => row.id}
            paginator={courseResultInfo}
            validFilters={[
              'course',
              'classification',
              'academicSession',
              'term',
            ]}
            onFilterButtonClick={courseResultInfoFilterToggle.open}
          />
        </SlabBody>
        <CourseResultInfoTableFilters {...courseResultInfoFilterToggle.props} />
      </Slab>
    </DashboardLayout>
  );
}
