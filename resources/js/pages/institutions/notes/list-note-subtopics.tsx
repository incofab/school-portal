import React from 'react';
import { NoteSubTopic, NoteTopic } from '@/types/models';
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
  noteTopic: NoteTopic;
  noteSubTopics: PaginationResponse<NoteSubTopic>;
}

export default function ListNoteSubTopics({ noteTopic, noteSubTopics }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();
  const isStudent = useIsStudent();

  async function deleteItem(obj: NoteTopic) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('note-sub-topics.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<NoteSubTopic>[] = [
    {
      label: 'Parent Topic',
      value: 'classification.title',
      render: (row) => <Text>{row.note_topic?.title}</Text>,
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
            href={instRoute('note-sub-topics.show', [row.id])}
            variant={'link'}
            title="View"
          />

          {(isAdmin || isTeacher) && (
            <>
              <IconButton
                aria-label={'Edit Topic'}
                icon={<Icon as={PencilIcon} />}
                as={InertiaLink}
                href={instRoute('note-sub-topics.edit', [row.id])}
                variant={'ghost'}
                colorScheme={'brand'}
              />
              <DestructivePopover
                label={'Delete this Note?'}
                onConfirm={() => deleteItem(row)}
                isLoading={deleteForm.processing}
              >
                <IconButton
                  aria-label={'Delete note'}
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
          title="Sub-Topics"
          rightElement={
            !isStudent && (
              <LinkButton
                href={instRoute('note-sub-topics.create', [noteTopic.id])}
                title={'New'}
              />
            )
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={noteSubTopics.data}
            keyExtractor={(row) => row.id}
            paginator={noteSubTopics}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
