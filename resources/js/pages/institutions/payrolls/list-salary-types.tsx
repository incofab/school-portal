import React from 'react';
import { SalaryType } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { IconButton, Icon, HStack, Text } from '@chakra-ui/react';
import { PencilIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useWebForm from '@/hooks/use-web-form';
import DestructivePopover from '@/components/destructive-popover';
import DashboardLayout from '@/layout/dashboard-layout';
import { BrandButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import CreateEditSalaryTypeModal from '@/components/modals/payrolls/create-edit-salary-type-modal';

interface Props {
  salaryTypes: PaginationResponse<SalaryType>;
  salaryTypesArray: SalaryType[];
}

export default function ListStaffTypes({
  salaryTypes,
  salaryTypesArray,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();
  const editSalaryTypeModal = useModalValueToggle<SalaryType | undefined>();

  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: SalaryType) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('salary-types.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<SalaryType>[] = [
    {
      label: 'Title',
      render: (row) => <Text whiteSpace={'nowrap'}>{row.title}</Text>,
    },
    {
      label: 'Type',
      render: (row) => <Text whiteSpace={'nowrap'}>{row.type}</Text>,
    },
    {
      label: 'Percentage',
      render: (row) => (
        <Text whiteSpace={'nowrap'}>
          {row.percentage ? `${row.percentage}% of ${row.parent?.title}` : ''}
        </Text>
      ),
    },
    {
      label: 'Description',
      render: (row) => <Text whiteSpace={'nowrap'}>{row.description}</Text>,
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            aria-label={'Edit user'}
            icon={<Icon as={PencilIcon} />}
            onClick={() => openModal(row)}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          <DestructivePopover
            label={'Do you really want to delete this record? Be careful!!!'}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete salary'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>
        </HStack>
      ),
    },
  ];

  function openModal(salaryType?: SalaryType) {
    editSalaryTypeModal.open(salaryType ?? ({} as SalaryType));
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Salary Components"
          rightElement={
            <HStack>
              <BrandButton
                leftIcon={<Icon as={PlusIcon} />}
                title="Add New"
                onClick={() => openModal()}
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={salaryTypes.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={salaryTypes}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      {/* <UsersTableFilters {...userFilterToggle.props} /> */}

      {editSalaryTypeModal.state != undefined && (
        <CreateEditSalaryTypeModal
          salaryType={editSalaryTypeModal.state}
          salaryTypes={salaryTypesArray}
          {...editSalaryTypeModal.props}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </DashboardLayout>
  );
}
