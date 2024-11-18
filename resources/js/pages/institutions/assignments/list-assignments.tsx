import React from 'react';
import { Assignment } from '@/types/models';
import { HStack, IconButton, Icon, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import DateTimeDisplay from '@/components/date-time-display';
import { dateTimeFormat } from '@/util/util';
import useIsStudent from '@/hooks/use-is-student';
import useIsTeacher from '@/hooks/use-is-teacher';

interface Props {
  assignments: PaginationResponse<Assignment>;
}

export default function ListEvents({ assignments }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();
  const isStudent = useIsStudent();

  async function deleteItem(obj: Assignment) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('assignments.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['assignments'] });
  }

  const headers: ServerPaginatedTableHeader<Assignment>[] = [
    {
      label: 'Class',
      value: 'classification.title',
    },
    {
      label: 'Subject',
      value: 'course.title',
      render: (row) => <Text>{row.course?.title}</Text>,
    },
    {
      label: 'Submission Deadline',
      value: 'expires_at',
      render: (row) => (
        <DateTimeDisplay
          dateTime={row.expires_at}
          dateTimeformat={dateTimeFormat}
        />
      ),
    },
    {
      label: 'Action',
      render: (row: Assignment) => (
        <HStack>
          <LinkButton
            href={instRoute('assignments.show', [row.id])}
            variant={'link'}
            title="View"
          />

          {(isAdmin || isTeacher) && (
            <>
              <IconButton
                aria-label={'Edit Assignment'}
                icon={<Icon as={PencilIcon} />}
                as={InertiaLink}
                href={instRoute('assignments.edit', [row.id])}
                variant={'ghost'}
                colorScheme={'brand'}
              />
              <DestructivePopover
                label={'Delete this assignment'}
                onConfirm={() => deleteItem(row)}
                isLoading={deleteForm.processing}
              >
                <IconButton
                  aria-label={'Delete assignment'}
                  icon={<Icon as={TrashIcon} />}
                  variant={'ghost'}
                  colorScheme={'red'}
                />
              </DestructivePopover>

              <LinkButton
                href={instRoute('assignment-submission.submissions', [row.id])}
                variant={'link'}
                title="Submissions"
              />
            </>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List of Assignments"
          rightElement={
            !isStudent && (
              <LinkButton
                href={instRoute('assignments.create')}
                title={'New'}
              />
            )
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={assignments.data}
            keyExtractor={(row) => row.id}
            paginator={assignments}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
