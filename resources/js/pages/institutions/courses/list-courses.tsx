import React from 'react';
import { Course } from '@/types/models';
import {
  HStack,
  Icon,
  IconButton,
  Menu,
  MenuButton,
  MenuDivider,
  MenuItem,
  MenuList,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { EllipsisVerticalIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PlusIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import useIsTeacher from '@/hooks/use-is-teacher';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import PracticeQuestionModal from '@/components/modals/practice-question-modal';
import { InstitutionPermission } from '@/types/permissions';
import PermissionGate from '@/components/permission-gate';

interface Props {
  courses: PaginationResponse<Course>;
  assignedCourseIds?: number[];
}

export default function ListCourse({ courses, assignedCourseIds = [] }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();
  const practiceQuestionModalToggle = useModalValueToggle<Course>();

  async function deleteItem(obj: Course) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('courses.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['courses'] });
  }

  const headers: ServerPaginatedTableHeader<Course>[] = [
    {
      label: 'Order',
      value: 'order',
    },
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Action',
      render: (row: Course) => (
        <HStack>
          <PermissionGate
            permissions={[
              InstitutionPermission.NaturalAccess,
              InstitutionPermission.NaturalAccess,
            ]}
          >
            <BrandButton
              onClick={() => practiceQuestionModalToggle.open(row)}
              leftIcon={<Icon as={PlusIcon} />}
              variant={'ghost'}
              colorScheme={'brand'}
              title="Practice Questions"
            />
          </PermissionGate>
          <PermissionGate permissions={InstitutionPermission.NaturalAccess}>
            <Menu placement="bottom-end">
              <MenuButton
                as={IconButton}
                aria-label={`More actions for ${row.title}`}
                icon={<Icon as={EllipsisVerticalIcon} />}
                variant="ghost"
                colorScheme="brand"
                size="sm"
              />
              <MenuList>
                <MenuItem
                  as="a"
                  href={instRoute('lesson-plans.index', {
                    course: row.id,
                  })}
                >
                  Lesson Plans
                </MenuItem>
                <MenuItem
                  as={InertiaLink}
                  href={instRoute('courses.lesson-notes', [row.id])}
                >
                  Lesson Notes
                </MenuItem>
                <MenuItem
                  as="a"
                  href={instRoute('inst-topics.index', {
                    course: row.id,
                  })}
                >
                  List Topics
                </MenuItem>
                <PermissionGate
                  permissions={InstitutionPermission.NaturalAccess}
                  when={
                    isAdmin || (isTeacher && assignedCourseIds.includes(row.id))
                  }
                >
                  <MenuItem
                    as="a"
                    href={instRoute('course-sessions.index', [row.id])}
                  >
                    Question Bank
                  </MenuItem>
                </PermissionGate>

                {isAdmin && (
                  <>
                    <MenuDivider />
                    <MenuItem
                      as="a"
                      href={instRoute('courses.upload-content.create', [
                        row.id,
                      ])}
                    >
                      Upload Content
                    </MenuItem>
                    <MenuItem
                      as={InertiaLink}
                      href={instRoute('courses.edit', [row.id])}
                    >
                      Edit Subject
                    </MenuItem>
                    <DestructivePopover
                      label="Delete this subject"
                      onConfirm={() => deleteItem(row)}
                      isLoading={deleteForm.processing}
                    >
                      <MenuItem color="red.500">Delete Subject</MenuItem>
                    </DestructivePopover>
                  </>
                )}
              </MenuList>
            </Menu>
          </PermissionGate>
        </HStack>
      ),
    },
  ];

  function redirectUser() {
    Inertia.visit(instRoute('courses.view-practice-questions'));
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Subjects"
          rightElement={
            <HStack spacing={2}>
              <PermissionGate
                permissions={[
                  InstitutionPermission.NaturalAccess,
                  InstitutionPermission.NaturalAccess,
                ]}
              >
                <LinkButton
                  href={instRoute('courses.practice-progress')}
                  title={'Practice Progress'}
                  colorScheme="green"
                />
              </PermissionGate>
              {isAdmin ? (
                <>
                  <LinkButton
                    href={instRoute('courses.create')}
                    title={'New'}
                    colorScheme="brand"
                  />
                  <LinkButton
                    href={instRoute('courses.multi-create')}
                    title={'Multi Create'}
                    colorScheme="orange"
                  />
                </>
              ) : null}
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={courses.data}
            keyExtractor={(row) => row.id}
            paginator={courses}
          />
        </SlabBody>
      </Slab>

      {practiceQuestionModalToggle.state && (
        <PracticeQuestionModal
          {...practiceQuestionModalToggle.props}
          course={practiceQuestionModalToggle.state}
          onSuccess={() => redirectUser()}
        />
      )}
    </DashboardLayout>
  );
}
