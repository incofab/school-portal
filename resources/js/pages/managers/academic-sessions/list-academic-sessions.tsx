import React from 'react';
import { AcademicSession } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Button, HStack, Icon, IconButton, Text } from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import route from '@/util/route';
import { Inertia } from '@inertiajs/inertia';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/solid';
import { formatAsDate } from '@/util/util';

interface Props {
  academicSessions: PaginationResponse<AcademicSession>;
}

export default function ListAcademicSessions({ academicSessions }: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteAcademicSession(academicSession: AcademicSession) {
    if (!window.confirm('Do you want to delete this academic session?')) {
      return;
    }

    const res = await deleteForm.submit((data, web) =>
      web.delete(route('managers.academic-sessions.destroy', [academicSession]))
    );

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<AcademicSession>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Order',
      value: 'order_index',
    },
    {
      label: 'Created',
      value: 'created_at',
      render: (row) => formatAsDate(row.created_at),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={2}>
          <IconButton
            aria-label="Edit Academic Session"
            colorScheme="brand"
            size="sm"
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={route('managers.academic-sessions.edit', [row])}
          />
          <IconButton
            aria-label="Delete Academic Session"
            colorScheme="red"
            size="sm"
            icon={<Icon as={TrashIcon} />}
            onClick={() => deleteAcademicSession(row)}
          />
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Academic Sessions"
          rightElement={
            <Button
              as={InertiaLink}
              href={route('managers.academic-sessions.create')}
              colorScheme="brand"
              size="sm"
            >
              New
            </Button>
          }
        />
        <SlabBody>
          <Text mb={4} color="gray.600">
            Manage the global academic sessions used across the platform.
          </Text>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={academicSessions.data}
            keyExtractor={(row) => row.id}
            paginator={academicSessions}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
