import { CourseTeacher } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import route from '@/util/route';
import { IconButton, Icon, HStack } from '@chakra-ui/react';
import { TrashIcon } from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import React from 'react';
import useIsAdmin from '@/hooks/use-is-admin';
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

interface Props {
  courseTeachers: PaginationResponse<CourseTeacher>;
}

function ListLecturerCourses({ courseTeachers }: Props) {
  const isAdmin = useIsAdmin();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

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
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: CourseTeacher) => (
              <HStack>
                <LinkButton
                  title="Record Result"
                  href={instRoute('course-results.create', [row])}
                  variant={'link'}
                />
                <DestructivePopover
                  label={`Delete ${row.course!.title} assignment from ${
                    row.user!.full_name
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
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

export default ListLecturerCourses;
