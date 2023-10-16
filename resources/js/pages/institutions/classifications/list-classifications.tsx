import React from 'react';
import { Classification } from '@/types/models';
import { HStack, IconButton, Icon, Button, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import MigrateClassStudentsModal from '@/components/modals/migrate-class-students-modal';
import UploadClassificationModal from '@/components/modals/upload-classification-modal';

interface Props {
  classifications: PaginationResponse<Classification>;
}

export default function ListClassification({ classifications }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const migrateClassStudentsModalToggle = useModalValueToggle<Classification>();
  const uploadClassModalToggle = useModalToggle();

  async function deleteItem(obj: Classification) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('classifications.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['classifications'] });
  }

  const headers: ServerPaginatedTableHeader<Classification>[] = [
    {
      label: 'Group',
      value: 'classification_group.title',
    },
    {
      label: 'Title',
      value: 'title',
      render: (row) => <Text whiteSpace={'nowrap'}>{row.title}</Text>,
    },
    {
      label: 'Num of Students',
      value: 'students_count',
    },
    {
      label: 'Same Num of Subjects',
      render: (row) => (row.has_equal_subjects ? 'Yes' : 'No'),
    },
    {
      label: 'Form Teacher',
      render: (row) => row.form_teacher?.full_name ?? '',
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Classification) => (
              <HStack spacing={3}>
                <IconButton
                  aria-label={'Edit Class'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('classifications.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <BrandButton
                  title="Move Students"
                  onClick={() => migrateClassStudentsModalToggle.open(row)}
                />
                <DestructivePopover
                  label={'Delete this class'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete Class'}
                    icon={<Icon as={TrashIcon} />}
                    variant={'ghost'}
                    colorScheme={'red'}
                  />
                </DestructivePopover>
                <LinkButton
                  title="Student Tiles"
                  href={instRoute('classifications.students', [row])}
                  variant={'link'}
                />
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Classes"
          rightElement={
            <HStack>
              <LinkButton
                href={instRoute('classifications.create')}
                title={'New'}
              />
              <BrandButton
                title="Upload Classes"
                onClick={uploadClassModalToggle.open}
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={classifications.data}
            keyExtractor={(row) => row.id}
            paginator={classifications}
          />
        </SlabBody>
      </Slab>
      <UploadClassificationModal
        {...uploadClassModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
      {migrateClassStudentsModalToggle.state && (
        <MigrateClassStudentsModal
          {...migrateClassStudentsModalToggle.props}
          Classification={migrateClassStudentsModalToggle.state}
          onSuccess={() => Inertia.reload({ only: ['classifications'] })}
        />
      )}
    </DashboardLayout>
  );
}
