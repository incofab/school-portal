import React from 'react';
import {
  HStack,
  Icon,
  IconButton,
  Menu,
  MenuButton,
  MenuItem,
  MenuList,
  Text,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { CourseResultInfo } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { PaginationResponse } from '@/types/types';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import startCase from 'lodash/startCase';
import route from '@/util/route';
import useSharedProps from '@/hooks/use-shared-props';
import CourseResultInfoTableFilters from '@/components/table-filters/course-result-info-table-filters';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsStaff from '@/hooks/use-is-staff';
import useIsAdmin from '@/hooks/use-is-admin';
import UploadCourseResultsModal from '@/components/modals/upload-course-results-modal';
import { CloudArrowDownIcon, TrashIcon } from '@heroicons/react/24/solid';
import { Inertia } from '@inertiajs/inertia';
import DownloadCourseResultModal from '@/components/modals/download-course-result-modal';
import { EllipsisVerticalIcon } from '@heroicons/react/24/outline';
import { InertiaLink } from '@inertiajs/inertia-react';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';

interface Props {
  courseResultInfo: PaginationResponse<CourseResultInfo>;
}

export default function ListCourseResultInfo({ courseResultInfo }: Props) {
  const { currentInstitution } = useSharedProps();
  const courseResultInfoFilterToggle = useModalToggle();
  const downloadCourseResultModalToggle = useModalToggle();
  const uploadCourseResultModalToggle = useModalValueToggle();
  const { instRoute } = useInstitutionRoute();
  const isStaff = useIsStaff();
  const isAdmin = useIsAdmin();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: CourseResultInfo) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('course-result-info.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['courseResultInfo'] });
  }

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
      render: (row) => (
        <Text>
          {startCase(row.term)} {row.for_mid_term ? 'Mid-' : ''} Term
        </Text>
      ),
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
        <HStack spacing={2}>
          <LinkButton
            href={route('institutions.course-results.index', {
              institution: currentInstitution.uuid,
              classification: row.classification_id,
              course: row.course_id,
              term: row.term,
              academicSession: row.academic_session_id,
              forMidTerm: row.for_mid_term,
            })}
            variant={'link'}
            title="Student Scores"
          />
          <Menu>
            <MenuButton
              as={IconButton}
              aria-label={'open action menu'}
              icon={<Icon as={EllipsisVerticalIcon} />}
              size={'sm'}
              variant={'ghost'}
            />
            <MenuList>
              {/* <MenuItem
                as={InertiaLink}
                href={route('institutions.course-results.index', {
                  institution: currentInstitution.uuid,
                  classification: row.classification_id,
                  course: row.course_id,
                  term: row.term,
                  academicSession: row.academic_session_id,
                  forMidTerm: row.for_mid_term,
                })}
                py={2}
              >
                Student Scores
              </MenuItem> */}
              {isStaff && (
                <MenuItem
                  as={InertiaLink}
                  href={instRoute('course-result-info.transfer.create', [
                    row.id,
                  ])}
                  py={2}
                >
                  Transfer
                </MenuItem>
              )}
            </MenuList>
          </Menu>
          {isAdmin && (
            <DestructivePopover
              label={'Delete this course result info'}
              onConfirm={() => deleteItem(row)}
              isLoading={deleteForm.processing}
            >
              <IconButton
                aria-label={'Delete Course Result Info'}
                icon={<Icon as={TrashIcon} />}
                variant={'ghost'}
                colorScheme={'red'}
              />
            </DestructivePopover>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Recorded Result Detail"
          rightElement={
            <HStack>
              {isStaff && (
                <>
                  <BrandButton
                    onClick={() =>
                      uploadCourseResultModalToggle.open(undefined)
                    }
                    title={'Upload Results'}
                  />
                  <BrandButton
                    leftIcon={<Icon as={CloudArrowDownIcon} />}
                    onClick={downloadCourseResultModalToggle.open}
                    title="Download"
                  />
                </>
              )}
            </HStack>
          }
        />
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
        <UploadCourseResultsModal
          {...uploadCourseResultModalToggle.props}
          onSuccess={() => Inertia.reload({ only: ['courseResultInfo'] })}
        />
        <DownloadCourseResultModal
          {...downloadCourseResultModalToggle.props}
          onSuccess={() => Inertia.reload({ only: ['courseResultInfo'] })}
        />
        <CourseResultInfoTableFilters {...courseResultInfoFilterToggle.props} />
      </Slab>
    </DashboardLayout>
  );
}
