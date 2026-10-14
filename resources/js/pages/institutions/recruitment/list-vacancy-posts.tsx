import React from 'react';
import { Badge, HStack, Icon, IconButton, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { VacancyPost } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { LinkButton } from '@/components/buttons';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PencilIcon } from '@heroicons/react/24/outline';
import { TrashIcon } from '@heroicons/react/24/solid';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import DateTimeDisplay from '@/components/date-time-display';
import ListEmptyState from '@/components/list-empty-state';

interface Props {
  vacancyPosts: PaginationResponse<VacancyPost>;
}

export default function ListVacancyPosts({ vacancyPosts }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(vacancyPost: VacancyPost) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('vacancy-posts.destroy', [vacancyPost.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['vacancyPosts'] });
  }

  const headers: ServerPaginatedTableHeader<VacancyPost>[] = [
    { label: 'Title', value: 'title' },
    { label: 'Department', value: 'department' },
    { label: 'Type', value: 'employment_type' },
    {
      label: 'Status',
      render: (row) => (
        <Badge colorScheme={row.is_published ? 'green' : 'gray'}>
          {row.is_published ? 'Published' : 'Draft'}
        </Badge>
      ),
    },
    {
      label: 'Applications',
      render: (row) => <Text>{row.recruitment_applications_count ?? 0}</Text>,
    },
    {
      label: 'Deadline',
      render: (row) =>
        row.application_deadline ? (
          <DateTimeDisplay dateTime={row.application_deadline} />
        ) : (
          <Text>-</Text>
        ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={3}>
          <LinkButton
            href={instRoute('recruitment-applications.index', [row.id])}
            colorScheme="brand"
            title="Applications"
            variant={'link'}
          />
          <IconButton
            aria-label="Edit Vacancy"
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={instRoute('vacancy-posts.edit', [row.id])}
            variant="ghost"
            colorScheme="brand"
          />
          <DestructivePopover
            label="Delete this vacancy post"
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label="Delete Vacancy"
              icon={<Icon as={TrashIcon} />}
              variant="ghost"
              colorScheme="red"
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
          title="Vacancy Posts"
          rightElement={
            <HStack>
              <LinkButton
                href={instRoute('recruitment.public-index')}
                title="Public Board"
                variant="outline"
              />
              <LinkButton
                href={instRoute('vacancy-posts.create')}
                title="New"
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll
            headers={headers}
            data={vacancyPosts.data}
            keyExtractor={(row) => row.id}
            paginator={vacancyPosts}
            emptyState={
              <ListEmptyState
                title="No vacancy posts yet"
                description="Publish vacancy posts to describe open positions and invite candidates to apply. Applications for each post can then be reviewed from the recruitment area."
                action={
                  <LinkButton
                    href={instRoute('vacancy-posts.create')}
                    title="Create vacancy post"
                  />
                }
              />
            }
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
