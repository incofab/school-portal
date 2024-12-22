import React from 'react';
import { ClassificationGroup, NoteTopic } from '@/types/models';
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
import useModalToggle from '@/hooks/use-modal-toggle';
import NoteTopicTableFilters from '@/components/table-filters/note-topic-table-filters';

interface Props {
  noteTopics: PaginationResponse<NoteTopic>;
  classificationGroups: ClassificationGroup[];
}

export default function ListNoteTopics({
  noteTopics,
  classificationGroups,
}: Props) {
  const noteTopicFilterToggle = useModalToggle();
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();
  const isStudent = useIsStudent();

  async function deleteItem(obj: NoteTopic) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('note-topics.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<NoteTopic>[] = [
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
      label: 'Topic',
      value: 'title',
      render: (row) => <Text>{row.title}</Text>,
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
      render: (row: NoteTopic) => (
        <HStack>
          <LinkButton
            href={instRoute('note-topics.show', [row.id])}
            variant={'link'}
            title="View"
          />

          {(isAdmin || isTeacher) && (
            <>
              <IconButton
                aria-label={'Edit Topic'}
                icon={<Icon as={PencilIcon} />}
                as={InertiaLink}
                href={instRoute('note-topics.edit', [row.id])}
                variant={'ghost'}
                colorScheme={'brand'}
              />
              <DestructivePopover
                label={'Delete this topic'}
                onConfirm={() => deleteItem(row)}
                isLoading={deleteForm.processing}
              >
                <IconButton
                  aria-label={'Delete topic'}
                  icon={<Icon as={TrashIcon} />}
                  variant={'ghost'}
                  colorScheme={'red'}
                />
              </DestructivePopover>
            </>
          )}

          <LinkButton
            href={instRoute('note-sub-topics.list', [row.id])}
            variant={'link'}
            title="Sub-Topics"
          />
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List of Note Topics"
          rightElement={
            !isStudent && (
              <LinkButton
                href={instRoute('note-topics.create')}
                title={'New'}
              />
            )
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={noteTopics.data}
            keyExtractor={(row) => row.id}
            paginator={noteTopics}
            validFilters={[
              'classificationGroup',
              'classification',
              'courseTeacher',
              'course',
              'status',
              'term',
            ]}
            onFilterButtonClick={noteTopicFilterToggle.open}
          />
        </SlabBody>
        <NoteTopicTableFilters
          {...noteTopicFilterToggle.props}
          classificationGroups={classificationGroups}
        />
      </Slab>
    </DashboardLayout>
  );
}
