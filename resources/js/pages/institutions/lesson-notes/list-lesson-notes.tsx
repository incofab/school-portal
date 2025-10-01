import React from 'react';
import { ClassificationGroup, LessonNote } from '@/types/models';
import { HStack, IconButton, Icon, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { NoteStatusType, PaginationResponse } from '@/types/types';
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
import useModalToggle from '@/hooks/use-modal-toggle';
import LessonNoteTableFilters from '@/components/table-filters/lesson-note-table-filters';
import ButtonSwitch from '@/components/button-switch';

interface Props {
  lessonNotes: PaginationResponse<LessonNote>;
  classificationGroups: ClassificationGroup[];
}

export default function ListLessonNotes({
  lessonNotes,
  classificationGroups,
}: Props) {
  const lessonNoteFilterToggle = useModalToggle();
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const toggleStatusForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();
  const isStudent = useIsStudent();

  async function deleteItem(obj: LessonNote) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('lesson-notes.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  async function toggleStatus(obj: LessonNote) {
    const res = await toggleStatusForm.submit((data, web) =>
      web.post(instRoute('lesson-notes.toggle-publish', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<LessonNote>[] = [
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
      label: 'Title',
      value: 'title',
      render: (row) => <Text>{row.title}</Text>,
    },
    {
      label: 'Publish',
      render: (row) => (
        <ButtonSwitch
          items={[
            {
              label: 'Draft',
              value: NoteStatusType.Draft,
              onClick: isAdmin ? () => toggleStatus(row) : undefined,
            },
            {
              label: 'Publish',
              value: NoteStatusType.Published,
              onClick: isAdmin ? () => toggleStatus(row) : undefined,
            },
          ]}
          value={row.status}
          _disabled={toggleStatusForm.processing ? 'disabled' : ''}
        />
      ),
    },
    {
      label: 'Last Update',
      value: 'updated_at',
      render: (row) => (
        <DateTimeDisplay
          dateTime={row.updated_at}
          dateTimeformat={dateTimeFormat}
        />
      ),
    },
    {
      label: 'Action',
      render: (row: LessonNote) => (
        <HStack>
          <LinkButton
            href={instRoute('lesson-notes.show', [row.id])}
            variant={'link'}
            title="View"
          />

          {(isAdmin || isTeacher) && (
            <>
              <IconButton
                aria-label={'Edit Topic'}
                icon={<Icon as={PencilIcon} />}
                as={InertiaLink}
                href={instRoute('lesson-notes.edit', [row.id])}
                variant={'ghost'}
                colorScheme={'brand'}
              />
              <DestructivePopover
                label={'Delete this Lesson Note'}
                onConfirm={() => deleteItem(row)}
                isLoading={deleteForm.processing}
              >
                <IconButton
                  aria-label={'Delete Lesson Note'}
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

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Lesson Notes"
          rightElement={
            !isStudent && (
              <LinkButton
                href={instRoute('lesson-plans.index')}
                title={'New'}
              />
            )
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={lessonNotes.data}
            keyExtractor={(row) => row.id}
            paginator={lessonNotes}
            validFilters={[
              'classificationGroup',
              'classification',
              'courseTeacher',
              'course',
              'status',
              'term',
            ]}
            onFilterButtonClick={lessonNoteFilterToggle.open}
          />
        </SlabBody>
        <LessonNoteTableFilters
          {...lessonNoteFilterToggle.props}
          classificationGroups={classificationGroups}
        />
      </Slab>
    </DashboardLayout>
  );
}
