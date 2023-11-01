import React from 'react';
import { AdmissionApplication } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { EyeIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';

interface Props {
  admissionApplications: PaginationResponse<AdmissionApplication>;
}

export default function ListAdmissionApplication({
  admissionApplications,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: AdmissionApplication) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('admissions.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['admissions'] });
  }

  const headers: ServerPaginatedTableHeader<AdmissionApplication>[] = [
    {
      label: 'First Name',
      value: 'first_name',
    },
    {
      label: 'Last Name',
      value: 'last_name',
    },
    {
      label: 'Intended Class',
      value: 'intended_class_of_admission',
    },
    {
      label: 'Admission Status',
      value: 'admission_status',
    },
    ...(isAdmin || null
      ? [
          {
            label: 'Action',
            render: (row: AdmissionApplication) => (
              <HStack spacing={3}>
                <IconButton
                  aria-label={'View Application'}
                  icon={<Icon as={EyeIcon} />}
                  as={InertiaLink}
                  href={instRoute('admissions.show', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <DestructivePopover
                  label={'Delete this application'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete Application'}
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
        <SlabHeading title="List Admission Applications" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={admissionApplications.data}
            keyExtractor={(row) => row.id}
            paginator={admissionApplications}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
