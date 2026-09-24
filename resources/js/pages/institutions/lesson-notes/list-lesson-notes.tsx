import React from 'react';
import { LessonNote, LessonPlan } from '@/types/models';
import { Button, HStack, IconButton, Icon, Text } from '@chakra-ui/react';
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
import useModalToggle from '@/hooks/use-modal-toggle';
import LessonNoteTableFilters from '@/components/table-filters/lesson-note-table-filters';
import ButtonSwitch from '@/components/button-switch';
import useSharedProps from '@/hooks/use-shared-props';
import { InstitutionPermission } from '@/types/permissions';
import PermissionGate from '@/components/permission-gate';
import SelectLessonPlanModal from '@/components/modals/select-lesson-plan-modal';

interface Props {
  lessonNotes: PaginationResponse<LessonNote>;
  lessonPlans: LessonPlan[];
}

export default function ListLessonNotes({ lessonNotes, lessonPlans }: Props) {
  const lessonNoteFilterToggle = useModalToggle();
  const newLessonNoteToggle = useModalToggle();
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const toggleStatusForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const isStudent = useIsStudent();
  const { currentUser } = useSharedProps();

  function openLessonNoteCreation(lessonPlanId: number) {
    newLessonNoteToggle.close();
    Inertia.visit(instRoute('lesson-notes.create', [lessonPlanId]));
  }
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
        <PermissionGate
          permissions={InstitutionPermission.NaturalAccess}
          when={isAdmin}
        >
          <ButtonSwitch
            items={[
              {
                label: 'Draft',
                value: NoteStatusType.Draft,
                onClick: () => toggleStatus(row),
              },
              {
                label: 'Publish',
                value: NoteStatusType.Published,
                onClick: () => toggleStatus(row),
              },
            ]}
            value={row.status}
            _disabled={
              toggleStatusForm.processing
                ? { pointerEvents: 'none', opacity: 0.5 }
                : undefined
            }
          />
        </PermissionGate>
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

          <PermissionGate permissions={InstitutionPermission.NaturalAccess}>
            {isAdmin || row.course_teacher?.user_id === currentUser.id ? (
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
            ) : null}
          </PermissionGate>
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
            <PermissionGate permissions={InstitutionPermission.NaturalAccess}>
              {!isStudent && (
                <Button
                  title={'New Lesson Note'}
                  onClick={newLessonNoteToggle.open}
                  type="button"
                  colorScheme="brand"
                  size="sm"
                  fontWeight="normal"
                >
                  New Lesson Note
                </Button>
              )}
            </PermissionGate>
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
              // 'classification',
              'courseTeacher',
              'course',
              'status',
              'term',
            ]}
            onFilterButtonClick={lessonNoteFilterToggle.open}
          />
        </SlabBody>
        <LessonNoteTableFilters {...lessonNoteFilterToggle.props} />
        <SelectLessonPlanModal
          {...newLessonNoteToggle.props}
          lessonPlans={lessonPlans}
          onContinue={openLessonNoteCreation}
        />
      </Slab>
    </DashboardLayout>
  );
}
