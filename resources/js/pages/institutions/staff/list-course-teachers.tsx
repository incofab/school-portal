import { CourseTeacher } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import {
  IconButton,
  Icon,
  HStack,
  Menu,
  MenuButton,
  MenuList,
  MenuItem,
  Button,
} from '@chakra-ui/react';
import { TrashIcon } from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useWebForm from '@/hooks/use-web-form';
import DestructivePopover from '@/components/destructive-popover';
import DashboardLayout from '@/layout/dashboard-layout';
import { LinkButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import useIsStaff from '@/hooks/use-is-staff';
import useIsAdmin from '@/hooks/use-is-admin';
import useSharedProps from '@/hooks/use-shared-props';
import CourseTeacherTableFilters from '@/components/table-filters/course-teacher-table-filters';
import useModalToggle from '@/hooks/use-modal-toggle';
import { InertiaLink } from '@inertiajs/inertia-react';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';

interface Props {
  courseTeachers: PaginationResponse<CourseTeacher>;
}

function ListLecturerCourses({ courseTeachers }: Props) {
  const isStaff = useIsStaff();
  const isAdmin = useIsAdmin();
  const { currentUser } = useSharedProps();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const courseTeacherFilterModalToggle = useModalToggle();

  async function deleteItem(obj: CourseTeacher) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('course-teachers.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['courseTeachers'] });
  }

  const headers: ServerPaginatedTableHeader<CourseTeacher>[] = [
    {
      label: 'Teacher',
      value: 'user.full_name',
      render: (row) => <DisplayUserFullname user={row.user} />,
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
      label: 'Assigned On',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    ...(isStaff
      ? [
          {
            label: 'Action',
            render: (row: CourseTeacher) => (
              <HStack>
                {(isAdmin || currentUser.id === row.user_id) && (
                  <>
                    <Menu>
                      <MenuButton
                        as={Button}
                        variant={'link'}
                        colorScheme={'brand'}
                        fontWeight={'normal'}
                      >
                        Record Result
                      </MenuButton>
                      <MenuList>
                        <MenuItem
                          as={InertiaLink}
                          href={instRoute('course-results.create', [row])}
                          py={2}
                        >
                          Single Student
                        </MenuItem>
                        <MenuItem
                          as={InertiaLink}
                          href={instRoute('record-class-results.create', [row])}
                          py={2}
                        >
                          All Class Students
                        </MenuItem>
                      </MenuList>
                    </Menu>
                    <DestructivePopover
                      label={`Delete ${row.course?.title} assignment from ${row.user?.full_name}?`}
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
          title={'Subject Teachers'}
          rightElement={
            <LinkButton
              href={instRoute('course-teachers.create')}
              title="Assign"
            />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={courseTeachers.data}
            keyExtractor={(row) => row.id}
            paginator={courseTeachers}
            validFilters={['classification', 'course', 'teacher']}
            onFilterButtonClick={courseTeacherFilterModalToggle.open}
          />
        </SlabBody>
      </Slab>
      <CourseTeacherTableFilters {...courseTeacherFilterModalToggle.props} />
    </DashboardLayout>
  );
}

export default ListLecturerCourses;
