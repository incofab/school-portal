import React from 'react';
import { ClassificationGroup, LessonPlan } from '@/types/models';
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

interface Props {
  lessonPlans: PaginationResponse<LessonPlan>;
  classificationGroups: ClassificationGroup[];
}

export default function LessonPlans({
  lessonPlans,
  classificationGroups,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: LessonPlan) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('lesson-plans.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<LessonPlan>[] = [
    {
      label: 'Class',
      value: 'topic.classification_group.title',
      render: (row) => (
        <Text>{row.scheme_of_work?.topic?.classification_group?.title}</Text>
      ),
    },
    {
      label: 'Subject',
      value: 'course.title',
      render: (row) => <Text>{row.scheme_of_work?.topic?.course?.title}</Text>,
    },
    {
      label: 'Term',
      value: 'term',
      render: (row) => <Text>{row.scheme_of_work?.term}</Text>,
    },
    {
      label: 'Week Number',
      value: 'week_number',
      render: (row) => <Text>{row.scheme_of_work?.week_number}</Text>,
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
      render: (row: LessonPlan) => (
        <HStack>
          <LinkButton
            href={instRoute('lesson-plans.show', [row.id])}
            variant={'link'}
            title="View"
          />
          <IconButton
            aria-label={'Edit Lesson Plan'}
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={instRoute('lesson-plans.edit', [row.id])}
            variant={'ghost'}
            colorScheme={'brand'}
          />

          <DestructivePopover
            label={'Delete this Lesson Plan'}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete Plan'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>

          {row.lesson_note === null && (
            <LinkButton
              href={instRoute('lesson-notes.create', [row.id])}
              variant={'link'}
              title="Create Lesson Note"
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
          title="Lesson Plans"
          rightElement={
            <LinkButton
              href={instRoute('scheme-of-works.index')}
              title={'New'}
            />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={lessonPlans.data}
            keyExtractor={(row) => row.id}
            paginator={lessonPlans}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
