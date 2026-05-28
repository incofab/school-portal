import React from 'react';
import { Badge, HStack, Icon, IconButton, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { RecruitmentApplication, VacancyPost } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { EyeIcon } from '@heroicons/react/24/outline';
import { TrashIcon } from '@heroicons/react/24/solid';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { LinkButton } from '@/components/buttons';

interface Props {
  recruitmentApplications: PaginationResponse<RecruitmentApplication>;
  vacancyPost?: VacancyPost;
}

export default function ListRecruitmentApplications({
  recruitmentApplications,
  vacancyPost,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(application: RecruitmentApplication) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(
        instRoute('recruitment-applications.destroy', [application.id])
      )
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['recruitmentApplications'] });
  }

  const headers: ServerPaginatedTableHeader<RecruitmentApplication>[] = [
    { label: 'Name', value: 'name' },
    { label: 'Application No', value: 'application_no' },
    { label: 'Vacancy', value: 'vacancy_post.title' },
    { label: 'Email', value: 'email' },
    { label: 'Phone', value: 'phone' },
    {
      label: 'Status',
      render: (row) => (
        <Badge colorScheme={row.status === 'hired' ? 'green' : 'blue'}>
          {row.status}
        </Badge>
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={3}>
          <IconButton
            aria-label="View Application"
            icon={<Icon as={EyeIcon} />}
            as={InertiaLink}
            href={instRoute('recruitment-applications.show', [row.id])}
            variant="ghost"
            colorScheme="brand"
          />
          <DestructivePopover
            label="Delete this recruitment application"
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label="Delete Application"
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
          title={
            vacancyPost
              ? `${vacancyPost.title} Applications`
              : 'Recruitment Applications'
          }
          rightElement={
            <LinkButton
              href={instRoute('recruitment-applications.create', [
                vacancyPost?.id,
              ])}
              colorScheme="brand"
              title="Apply"
            />
          }
        />
        <SlabBody>
          {vacancyPost && (
            <Text color="gray.600" mb={4}>
              Review candidates who applied to this vacancy.
            </Text>
          )}
          <ServerPaginatedTable
            scroll
            headers={headers}
            data={recruitmentApplications.data}
            keyExtractor={(row) => row.id}
            paginator={recruitmentApplications}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
