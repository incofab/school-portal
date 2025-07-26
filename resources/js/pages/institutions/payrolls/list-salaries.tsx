import React from 'react';
import { SalaryType, Salary } from '@/types/models';
import { PaginationResponse, TransactionType } from '@/types/types';
import { IconButton, Icon, HStack, Text, Badge } from '@chakra-ui/react';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
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
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import { formatAsCurrency } from '@/util/util';
import CreateEditSalaryModal from '@/components/modals/payrolls/create-edit-salary-modal';
import { PlusIcon } from '@heroicons/react/24/solid';

interface Props {
  salaries: PaginationResponse<Salary>;
  salaryTypes: SalaryType[];
}

export default function ListSalaries({ salaries, salaryTypes }: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();

  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const editSalaryModal = useModalValueToggle<Salary | undefined>();

  async function deleteItem(obj: Salary) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('salaries.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['salaries'] });
  }

  const headers: ServerPaginatedTableHeader<Salary>[] = [
    {
      label: 'Staff Name',
      render: (row) => (
        <DisplayUserFullname user={row.institution_user?.user} />
      ),
    },
    {
      label: 'Title',
      render: (row) => (
        <Text whiteSpace={'nowrap'}>{row.salary_type?.title}</Text>
      ),
    },
    {
      label: 'Amount',
      render: (row) => (
        <Badge
          textShadow={`1px 1px 2px ${
            row.salary_type?.type === TransactionType.Credit
              ? 'rgba(0, 255, 0, 0.7)'
              : 'rgba(255, 0, 0, 0.7)'
          }`}
          color={
            row.salary_type?.type === TransactionType.Credit
              ? 'green.600'
              : 'red.600'
          }
        >
          {formatAsCurrency(row.amount)}
        </Badge>
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            aria-label={'Edit staff salary'}
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

  function openModal(salary?: Salary) {
    editSalaryModal.open(salary ?? ({} as Salary));
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Salaries"
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
            data={salaries.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={salaries}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      {/* <UsersTableFilters {...userFilterToggle.props} /> */}

      {editSalaryModal.state != undefined && (
        <CreateEditSalaryModal
          salary={editSalaryModal.state ?? null}
          salaryTypes={salaryTypes}
          {...editSalaryModal.props}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </DashboardLayout>
  );
}
