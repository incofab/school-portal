import React from 'react';
import { AcademicSession, Institution, InstitutionGroup } from '@/types/models';
import {
  Button,
  HStack,
  Icon,
  IconButton,
  Text,
  Tooltip,
} from '@chakra-ui/react';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { InstitutionStatus, PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import {
  PencilIcon,
  PlusIcon,
  TrashIcon,
  DocumentChartBarIcon,
} from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import ButtonSwitch from '@/components/button-switch';
import { Div } from '@/components/semantic';
import useIsAdminManager from '@/hooks/use-is-admin-manager';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import GenerateInvoiceModal from './generate-invoice-modal';

interface InstitutionGroupWithMeta extends InstitutionGroup {
  institutions_count: number;
  institutions: Institution[];
}

interface Props {
  institutionGroups: PaginationResponse<InstitutionGroupWithMeta>;
  stats: {
    active_count: number;
    suspended_count: number;
    total: number;
  };
  academicSessions: AcademicSession[];
}

function NumberFormatter(number: number) {
  return new Intl.NumberFormat().format(number);
}

export default function ListInstitutionGropus({
  institutionGroups,
  stats,
  academicSessions,
}: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const suspensionForm = useWebForm({});
  const isAdminManager = useIsAdminManager();
  const invoiceModalToggle = useModalValueToggle<InstitutionGroupWithMeta>();

  async function deleteInstitution(institutionGroup: InstitutionGroup) {
    if (!window.confirm('Do you want to delete this group?')) {
      return;
    }
    const res = await deleteForm.submit((data, web) =>
      web.delete(
        route('managers.institution-groups.destroy', [institutionGroup])
      )
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  async function updateStatus(
    institutionGroup: InstitutionGroup,
    status: InstitutionStatus
  ) {
    if (!window.confirm('Do you want to change status on this Group?')) {
      return;
    }
    const res = await suspensionForm.submit((data, web) =>
      web.post(
        route('managers.institution-groups.update.status', [institutionGroup]),
        { status }
      )
    );

    if (!handleResponseToast(res)) return;

    Inertia.reload({ only: ['institutionGroups'] });
  }

  const headers: ServerPaginatedTableHeader<InstitutionGroupWithMeta>[] = [
    {
      label: 'Partner',
      value: 'partner.full_name',
    },
    {
      label: 'Name',
      value: 'name',
    },
    {
      label: 'Institutions',
      value: 'institutions_count',
    },
    {
      label: 'Credit (₦)',
      value: 'credit_wallet',
      render: (row) => (
        <Text color={'green.500'}>{NumberFormatter(row.credit_wallet)}</Text>
      ),
    },
    {
      label: 'Debt (₦)',
      value: 'debt_wallet',
      render: (row) => (
        <Text color={'red.500'}>{NumberFormatter(row.debt_wallet)}</Text>
      ),
    },
    {
      label: 'Loan Limit (₦)',
      value: 'loan_limit',
      render: (row) => NumberFormatter(row.loan_limit),
    },
    {
      label: 'Status',
      render: (row) => (
        <ButtonSwitch
          items={[
            {
              value: InstitutionStatus.Active,
              label: InstitutionStatus.Active,
              onClick: () => updateStatus(row, InstitutionStatus.Active),
            },
            {
              value: InstitutionStatus.Suspended,
              label: InstitutionStatus.Suspended,
              onClick: () => updateStatus(row, InstitutionStatus.Suspended),
            },
          ]}
          value={row.status}
        />
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={2}>
          <Tooltip label="Generate Invoice">
            <IconButton
              aria-label="Generate Invoice"
              colorScheme={'brand'}
              size={'sm'}
              icon={<Icon as={DocumentChartBarIcon} />}
              onClick={() => invoiceModalToggle.open(row)}
              isDisabled={row.institutions_count === 0}
            />
          </Tooltip>
          <IconButton
            aria-label="Edit Group"
            colorScheme={'brand'}
            size={'sm'}
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={route('managers.institution-groups.edit', [row])}
          />
          <IconButton
            aria-label="Delete Group"
            colorScheme={'red'}
            size={'sm'}
            icon={<Icon as={TrashIcon} />}
            onClick={() => deleteInstitution(row)}
            isDisabled={row.institutions_count > 0}
          />
          <Tooltip label={'Add Institution'}>
            <IconButton
              aria-label="Add Institution"
              colorScheme={'brand'}
              size={'sm'}
              icon={<Icon as={PlusIcon} />}
              as={InertiaLink}
              href={route('managers.institutions.create', [row.id])}
            />
          </Tooltip>
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Groups"
          rightElement={
            <Button
              as={InertiaLink}
              href={route('managers.institution-groups.create')}
              colorScheme={'brand'}
              variant={'solid'}
              size={'sm'}
            >
              New
            </Button>
          }
        />
        <SlabBody>
          {isAdminManager && (
            <Div>
              Total: {stats.total} | Active: {stats.active_count} | Suspended:{' '}
              {stats.suspended_count}
            </Div>
          )}
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={institutionGroups.data}
            keyExtractor={(row) => row.id}
            paginator={institutionGroups}
            tableRowProps={(row) => ({
              backgroundColor:
                row.status == InstitutionStatus.Suspended
                  ? 'red.100'
                  : undefined,
            })}
          />
        </SlabBody>
      </Slab>
      {invoiceModalToggle.state && (
        <GenerateInvoiceModal
          {...invoiceModalToggle.props}
          institutionGroup={invoiceModalToggle.state}
          academicSessions={academicSessions}
        />
      )}
    </ManagerDashboardLayout>
  );
}
