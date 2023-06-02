import React from 'react';
import { Course } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';

interface Props {
  courses: PaginationResponse<Course>;
}

export default function ListCourse({ courses }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

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
    // {
    //   label: 'Created At',
    //   value: 'created_at',
    //   render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    // },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
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
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Subjects"
          rightElement={
            <LinkButton href={instRoute('courses.create')} title={'New'} />
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
    </DashboardLayout>
  );
}
