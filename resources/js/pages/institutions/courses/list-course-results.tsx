import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import { CourseResult, CourseTeacher } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { Button, HStack, Icon, IconButton, Text } from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import startCase from 'lodash/startCase';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton } from '@/components/buttons';
import useIsStaff from '@/hooks/use-is-staff';
import DashboardLayout from '@/layout/dashboard-layout';
import CourseResultsTableFilters from '@/components/table-filters/course-result-table-filters';
import UploadCourseResultsModal from '@/components/modals/upload-course-results-modal';
import useQueryString from '@/hooks/use-query-string';
import { CloudArrowDownIcon, PencilIcon } from '@heroicons/react/24/outline';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useIsAdmin from '@/hooks/use-is-admin';
import useSharedProps from '@/hooks/use-shared-props';
import DestructivePopover from '@/components/destructive-popover';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import { InertiaLink } from '@inertiajs/inertia-react';

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
  const isAdmin = useIsAdmin();
  const { currentUser } = useSharedProps();
  const { toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { params } = useQueryString();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: CourseResult) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('course-results.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['courseResults'] });
  }

  function canDownloadSheet() {
    return (
      params.academicSession &&
      params.course &&
      params.term &&
      params.classification
    );
  }

  function downloadSheet(e: any) {
    if (!canDownloadSheet()) {
      e.preventDefault();
      toastError(
        'You have to filter through the subject, class, academic session and term before downloading'
      );
      return false;
    }
    return true;
  }

  const headers: ServerPaginatedTableHeader<CourseResult>[] = [
    {
      label: 'Student',
      value: 'student.user.full_name',
    },
    {
      label: 'Subject',
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
      render: (row) => (
        <Text>
          {startCase(row.term)} {row.for_mid_term ? 'Mid-' : ''} Term
        </Text>
      ),
    },
    {
      label: 'Assessment',
      render: function (row) {
        return Object.entries(row.assessment_values ?? {})
          .map(([key, val]) => `${startCase(key)} = ${val}`)
          .join(',\n');
      },
    },
    {
      label: 'Exam',
      value: 'exam',
    },
    {
      label: 'Result',
      value: 'result',
      sortKey: 'result',
    },
    {
      label: 'Position',
      value: 'position',
      sortKey: 'position',
    },
    {
      label: 'Grade',
      value: 'grade',
    },
    {
      label: 'Teacher',
      value: 'teacher.full_name',
    },
    ...(isStaff
      ? [
          {
            label: 'Action',
            render: (row: CourseResult) => (
              <HStack>
                {(isAdmin || currentUser.id === row.teacher_user_id) && (
                  <>
                    <IconButton
                      as={InertiaLink}
                      aria-label={'Edit'}
                      icon={<Icon as={PencilIcon} />}
                      variant={'ghost'}
                      colorScheme={'brand'}
                      href={instRoute('course-results.edit', [row.id])}
                    />
                    <DestructivePopover
                      label={`Delete ${row.course?.title} result for ${
                        row.student?.user!.full_name
                      }?`}
                      onConfirm={() => deleteItem(row)}
                      isLoading={deleteForm.processing}
                    >
                      <IconButton
                        aria-label={'Delete'}
                        icon={<Icon as={TrashIcon} />}
                        variant={'ghost'}
                        colorScheme={'red'}
                      />
                    </DestructivePopover>
                  </>
                )}
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Student Results"
          rightElement={
            <HStack>
              {isStaff && (
                <>
                  <BrandButton
                    onClick={() =>
                      uploadCourseResultModalToggle.open(courseTeacher)
                    }
                    title={'Upload Results'}
                  />
                  <Button
                    as={'a'}
                    href={instRoute('course-results.download', [params])}
                    colorScheme={'brand'}
                    variant={'solid'}
                    size={'sm'}
                    leftIcon={<Icon as={CloudArrowDownIcon} />}
                    onClick={downloadSheet}
                  >
                    Download
                  </Button>
                </>
              )}
            </HStack>
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
