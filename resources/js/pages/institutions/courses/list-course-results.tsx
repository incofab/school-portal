import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import { CourseResult, CourseTeacher } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { Text } from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import startCase from 'lodash/startCase';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton } from '@/components/buttons';
import useIsStaff from '@/hooks/use-is-staff';
import DashboardLayout from '@/layout/dashboard-layout';
import CourseResultsTableFilters from '@/components/table-filters/course-result-table-filters';
import UploadCourseResultsModal from '@/components/modals/upload-course-results-modal';

interface Props {
  courseTeacher?: CourseTeacher;
  courseResults: PaginationResponse<CourseResult>;
}

export default function ListCourseResults({
  courseResults,
  courseTeacher,
}: Props) {
  const uploadCourseResultModalToggle = useModalValueToggle<
    CourseTeacher | undefined
  >();
  const courseResultFilterToggle = useModalToggle();
  const isStaff = useIsStaff();
  const headers: ServerPaginatedTableHeader<CourseResult>[] = [
    {
      label: 'User',
      value: 'student.user.full_name',
    },
    {
      label: 'Subject',
      value: 'course.title',
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
      label: '1st CA',
      value: 'first_assessment',
    },
    {
      label: '2nd CA',
      value: 'second_assessment',
    },
    {
      label: 'Result',
      value: 'result',
    },
    {
      label: 'Grade',
      value: 'grade',
    },
    {
      label: 'Teacher',
      value: 'teacher.full_name',
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Student Results"
          rightElement={
            isStaff && (
              <BrandButton
                onClick={() => {
                  console.log(
                    'd,ksmkmsddsdd vjnsdvksdv',
                    uploadCourseResultModalToggle.isOpen
                  );

                  uploadCourseResultModalToggle.open(courseTeacher);
                }}
                title={'Upload Results'}
              />
            )
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={courseResults.data}
            keyExtractor={(row) => row.id}
            paginator={courseResults}
            validFilters={[
              'classification',
              'academicSession',
              'course',
              'student',
              'teacher',
              'term',
            ]}
            onFilterButtonClick={courseResultFilterToggle.open}
          />
        </SlabBody>
        <UploadCourseResultsModal
          {...uploadCourseResultModalToggle.props}
          courseTeacher={uploadCourseResultModalToggle.state ?? undefined}
          onSuccess={() => Inertia.reload({ only: ['courseResults'] })}
        />
        <CourseResultsTableFilters {...courseResultFilterToggle.props} />
      </Slab>
    </DashboardLayout>
  );
}
