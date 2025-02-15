import React from 'react';
import { ClassificationGroup, Topic, SchemeOfWork } from '@/types/models';
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
import DateTimeDisplay from '@/components/date-time-display';
import { dateTimeFormat } from '@/util/util';
import useModalToggle from '@/hooks/use-modal-toggle';
import NoteTopicTableFilters from '@/components/table-filters/note-topic-table-filters';
import useIsAdmin from '@/hooks/use-is-admin';

interface Props {
  schemeOfWorks: PaginationResponse<SchemeOfWork>;
  classificationGroups: ClassificationGroup[];
}

export default function ListSchemeOfWork({
  schemeOfWorks,
  classificationGroups,
}: Props) {
  // const noteTopicFilterToggle = useModalToggle();
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: SchemeOfWork) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('scheme-of-works.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<SchemeOfWork>[] = [
    {
      label: 'Class',
      value: 'topic.classification_group.title',
      render: (row) => <Text>{row.topic?.classification_group?.title}</Text>,
    },
    {
      label: 'Subject',
      value: 'course.title',
      render: (row) => <Text>{row.topic?.course?.title}</Text>,
    },
    {
      label: 'Term',
      value: 'term',
      render: (row) => <Text>{row.term}</Text>,
    },
    {
      label: 'Week Number',
      value: 'week_number',
      render: (row) => <Text>{row.week_number}</Text>,
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
      render: (row: SchemeOfWork) => (
        <HStack>
          <LinkButton
            href={instRoute('scheme-of-works.show', [row.id])}
            variant={'link'}
            title="View"
          />
          <IconButton
            aria-label={'Edit Scheme'}
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={instRoute('scheme-of-works.edit', [row.id])}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          <DestructivePopover
            label={'Delete this Scheme of Work'}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete Scheme'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>
          {row.lesson_plan === null && (
            <LinkButton
              href={instRoute('lesson-plans.create', [row.id])}
              variant={'link'}
              title="Create Lesson Plan"
            />
          )}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Scheme of Works"
          rightElement={
            isAdmin && (
              <LinkButton
                href={instRoute('scheme-of-works.create')}
                title={'New'}
              />
            )
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={schemeOfWorks.data}
            keyExtractor={(row) => row.id}
            paginator={schemeOfWorks}
            // validFilters={['classificationGroup', 'course', 'term']}
            // onFilterButtonClick={noteTopicFilterToggle.open}
          />
        </SlabBody>
        {/* 
        <NoteTopicTableFilters
          {...noteTopicFilterToggle.props}
          classificationGroups={classificationGroups}
        /> 
        */}
      </Slab>
    </DashboardLayout>
  );
}
