import React from 'react';
import { Course } from '@/types/models';
import { HStack, IconButton, Icon, Button } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  CloudArrowUpIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import PracticeQuestionModal from '@/components/modals/practice-question-modal';
import useIsTeacher from '@/hooks/use-is-teacher';

interface Props {
  courses: PaginationResponse<Course>;
}

export default function ListCourse({ courses }: Props) {
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
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Action',
      render: (row: Course) => (
        <HStack>
          <BrandButton
            onClick={() => practiceQuestionModalToggle.open(row)}
            leftIcon={<Icon as={PlusIcon} />}
            variant={'ghost'}
            colorScheme={'brand'}
            title="Practice Question"
          />
          {(isAdmin || isTeacher) && (
            <Button
              as={'a'}
              href={instRoute('course-sessions.index', [row.id])}
              variant={'link'}
              colorScheme={'brand'}
            >
              Question Bank
            </Button>
          )}

          {isAdmin && (
            <>
              <IconButton
                aria-label={'Upload Content'}
                icon={<Icon as={CloudArrowUpIcon} />}
                as={'a'}
                href={instRoute('courses.upload-content.create', [row.id])}
                variant={'ghost'}
                colorScheme={'brand'}
              />

              <IconButton
                aria-label={'Edit Subject'}
                icon={<Icon as={PencilIcon} />}
                as={InertiaLink}
                href={instRoute('courses.edit', [row.id])}
                variant={'ghost'}
                colorScheme={'brand'}
              />

              <DestructivePopover
                label={'Delete this subject'}
                onConfirm={() => deleteItem(row)}
                isLoading={deleteForm.processing}
              >
                <IconButton
                  aria-label={'Delete subject'}
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
            isAdmin ? (
              <HStack spacing={2}>
                <LinkButton href={instRoute('courses.create')} title={'New'} />
                <LinkButton
                  href={instRoute('courses.multi-create')}
                  title={'Multi Create'}
                />
              </HStack>
            ) : (
              <></>
            )
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
