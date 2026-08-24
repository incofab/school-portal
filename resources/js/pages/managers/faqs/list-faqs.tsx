import React from 'react';
import { Faq } from '@/types/models';
import { FaqType } from '@/types/types';
import { PaginationResponse } from '@/types/types';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import {
  Badge,
  Button,
  HStack,
  Icon,
  IconButton,
  Text,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import route from '@/util/route';
import { Inertia } from '@inertiajs/inertia';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { EyeIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/solid';
import { formatAsDate } from '@/util/util';
import useModalToggle from '@/hooks/use-modal-toggle';
import FaqsTableFilters from '@/components/table-filters/faqs-table-filters';

interface Props {
  faqs: PaginationResponse<Faq>;
}

export default function ListFaqs({ faqs }: Props) {
  const filterToggle = useModalToggle();
  const deleteForm = useWebForm({});
  const toggleForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteFaq(faq: Faq) {
    if (!window.confirm('Do you want to delete this FAQ?')) {
      return;
    }

    const res = await deleteForm.submit((data, web) =>
      web.delete(route('managers.faqs.destroy', [faq]))
    );

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.reload();
  }

  async function toggleFaq(faq: Faq) {
    const res = await toggleForm.submit((data, web) =>
      web.post(route('managers.faqs.toggle', [faq]))
    );

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<Faq>[] = [
    {
      label: 'Question',
      value: 'name',
    },
    {
      label: 'Code',
      value: 'code',
    },
    {
      label: 'Type',
      value: 'type',
      render: (row) => (
        <Badge colorScheme={row.type === FaqType.Faq ? 'blue' : 'purple'}>
          {row.type === FaqType.Faq ? 'FAQ' : 'Knowledge Base'}
        </Badge>
      ),
    },
    {
      label: 'Order',
      value: 'sort_order',
      render: (row) => String(row.sort_order ?? 'Auto'),
    },
    {
      label: 'Status',
      value: 'is_active',
      render: (row) =>
        row.is_active ? (
          <Badge colorScheme="green">Active</Badge>
        ) : (
          <Badge colorScheme="gray">Inactive</Badge>
        ),
    },
    {
      label: 'Video',
      value: 'youtube_video_id',
      render: (row) =>
        row.youtube_video_id ? (
          <Badge colorScheme="red">YouTube</Badge>
        ) : (
          <Badge colorScheme="gray">None</Badge>
        ),
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
            aria-label="Preview FAQ"
            colorScheme="purple"
            size="sm"
            icon={<Icon as={EyeIcon} />}
            as={InertiaLink}
            href={route('managers.faqs.show', [row])}
          />
          <IconButton
            aria-label="Edit FAQ"
            colorScheme="brand"
            size="sm"
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={route('managers.faqs.edit', [row])}
          />
          <Button
            colorScheme={row.is_active ? 'gray' : 'green'}
            size="sm"
            onClick={() => toggleFaq(row)}
            isLoading={toggleForm.processing}
          >
            {row.is_active ? 'Deactivate' : 'Activate'}
          </Button>
          <IconButton
            aria-label="Delete FAQ"
            colorScheme="red"
            size="sm"
            icon={<Icon as={TrashIcon} />}
            onClick={() => deleteFaq(row)}
          />
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="FAQs & Knowledge Base"
          rightElement={
            <Button
              as={InertiaLink}
              href={route('managers.faqs.create')}
              colorScheme="brand"
              size="sm"
            >
              New
            </Button>
          }
        />
        <SlabBody>
          <Text mb={4} color="gray.600">
            Manage public FAQs and product guides from one content library.
          </Text>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={faqs.data}
            keyExtractor={(row) => row.id}
            paginator={faqs}
            validFilters={['type']}
            onFilterButtonClick={filterToggle.open}
          />
        </SlabBody>
      </Slab>
      <FaqsTableFilters {...filterToggle.props} />
    </ManagerDashboardLayout>
  );
}
