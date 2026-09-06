import React from 'react';
import {
  Badge,
  HStack,
  Icon,
  IconButton,
  Text,
  VStack,
} from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { Role } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { LinkButton } from '@/components/buttons';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { ucFirst } from '@/util/util';

interface Props {
  roles: PaginationResponse<Role>;
}

export default function ListRoles({ roles }: Props) {
  const { instRoute } = useInstitutionRoute();
  const form = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteRole(role: Role) {
    const response = await form.submit((_, web) =>
      web.delete(instRoute('roles.destroy', [role]))
    );
    if (!handleResponseToast(response)) return;
    Inertia.reload({ only: ['roles'] });
  }

  const headers: ServerPaginatedTableHeader<Role>[] = [
    {
      label: 'Role',
      render: (role) => (
        <VStack align="start" spacing={0}>
          <Text fontWeight="semibold">{ucFirst(role.name)}</Text>
          {role.default_permissions_seeded && (
            <Badge colorScheme="blue">Built-in</Badge>
          )}
        </VStack>
      ),
    },
    {
      label: 'Description',
      render: (role) => role.description || 'No description provided',
    },
    {
      label: 'Permissions',
      render: (role) => String(role.permissions_count ?? 0),
    },
    {
      label: 'Assigned users',
      render: (role) => String(role.institution_users_count ?? 0),
    },
    {
      label: 'Actions',
      render: (role) => (
        <HStack>
          <IconButton
            as={InertiaLink}
            aria-label={`Edit ${role.name}`}
            icon={<Icon as={PencilIcon} />}
            href={instRoute('roles.edit', [role])}
            variant="ghost"
            colorScheme="brand"
          />
          <DestructivePopover
            label={`Delete the ${role.name} role? Users assigned to it will lose its permissions.`}
            onConfirm={() => deleteRole(role)}
            isLoading={form.processing}
            positiveButtonLabel="Delete role"
          >
            <IconButton
              aria-label={`Delete ${role.name}`}
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
          title="Roles and permissions"
          rightElement={
            <LinkButton href={instRoute('roles.create')} title="Create role" />
          }
        />
        <SlabBody>
          <Text color="gray.600" mb={4}>
            Define what each school role can access. Built-in roles are created
            during institution setup, can still be customised, and are
            backfilled for existing institutions by the provisioning command.
          </Text>
          <ServerPaginatedTable
            scroll
            hideSearchField
            headers={headers}
            data={roles.data}
            keyExtractor={(role) => role.id}
            paginator={roles}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
