import React from 'react';
import { AdmissionForm } from '@/types/models';
import { HStack, IconButton, Icon, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { CloudArrowUpIcon, TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import DateTimeDisplay from '@/components/date-time-display';
import { LinkButton } from '@/components/buttons';
import { PencilIcon } from '@heroicons/react/24/outline';
import { InertiaLink } from '@inertiajs/inertia-react';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import UploadAdmissionApplicationModal from '@/components/modals/upload-admission-applications-modal';

interface Props {
  admissionForms: PaginationResponse<AdmissionForm>;
}

export default function ListAdmissionForms({ admissionForms }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const uploadModal = useModalValueToggle<AdmissionForm>();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: AdmissionForm) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('admission-forms.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['admissionForms'] });
  }

  const headers: ServerPaginatedTableHeader<AdmissionForm>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Description',
      value: 'description',
    },
    {
      label: 'Price',
      value: 'price',
    },
    {
      label: 'Is Published',
      value: 'is_published',
      render: (row) => <Text>{row.is_published ? 'Yes' : 'No'}</Text>,
    },
    {
      label: 'Date',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    ...(isAdmin || null
      ? [
          {
            label: 'Action',
            render: (row: AdmissionForm) => (
              <HStack spacing={3}>
                <IconButton
                  aria-label={'Edit Admission Form'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('admission-forms.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <IconButton
                  aria-label={'Upload Admission Applications'}
                  icon={<Icon as={CloudArrowUpIcon} />}
                  variant={'ghost'}
                  colorScheme={'brand'}
                  onClick={() => uploadModal.open(row)}
                />
                <LinkButton
                  variant={'link'}
                  href={instRoute('admission-applications.index', [row.id])}
                  title="Applications"
                />
                <DestructivePopover
                  label={'Delete this Admission form'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete Admission form'}
                    icon={<Icon as={TrashIcon} />}
                    variant={'ghost'}
                    colorScheme={'red'}
                  />
                </DestructivePopover>
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
          title="List Admission Forms"
          rightElement={
            <LinkButton
              href={instRoute('admission-forms.create')}
              title="New"
            />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={admissionForms.data}
            keyExtractor={(row) => row.id}
            paginator={admissionForms}
          />
        </SlabBody>
      </Slab>
      {uploadModal.state && (
        <UploadAdmissionApplicationModal
          {...uploadModal.props}
          admissionForm={uploadModal.state}
          onSuccess={() =>
            Inertia.visit(
              instRoute('admission-applications.index', [uploadModal.state!.id])
            )
          }
        />
      )}
    </DashboardLayout>
  );
}
