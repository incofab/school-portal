import React from 'react';
import { ClassificationGroup, Topic } from '@/types/models';
import {
  HStack,
  IconButton,
  Icon,
  Text,
  Heading,
  Center,
} from '@chakra-ui/react';
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
import { searchContent } from '@/util/search-content';

interface Props {
  subtopics: PaginationResponse<Topic>;
  classificationGroups: ClassificationGroup[];
  parentTopic: Topic;
}

export default function ListSubTopics({ subtopics, parentTopic }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(topic: Topic) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('inst-topics.destroy', [topic.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<Topic>[] = [
    {
      label: 'Title',
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
      render: (row: Topic) => (
        <HStack>
          <LinkButton
            href={instRoute('inst-topics.show', [row.id])}
            variant={'link'}
            title="View"
          />
          <IconButton
            aria-label={'Edit Topic'}
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={instRoute('inst-topics.create-or-edit', [row.id])}
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
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`List of Sub-Topics`}
          rightElement={
            <LinkButton
              href={instRoute('inst-topics.create-or-edit')}
              title={'New'}
            />
          }
        />
        <SlabBody>
          <Center>
            <Heading size={'md'} fontWeight={'bold'} paddingBottom="10px">
              {`${parentTopic.classification_group?.title} - ${parentTopic.course?.title}`}
            </Heading>
          </Center>
          <Center>
            <Heading size={'sm'} fontWeight={'bold'} paddingBottom="40px">
              Parent Topic :: {parentTopic.title}
            </Heading>
          </Center>

          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={subtopics.data}
            keyExtractor={(row) => row.id}
            paginator={subtopics}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
