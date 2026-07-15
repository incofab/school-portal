import React, { useState } from 'react';
import { Button, HStack, Input, SimpleGrid, VStack } from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { FormButton } from '@/components/buttons';
import { Div } from '@/components/semantic';
import route from '@/util/route';
import { preventNativeSubmit } from '@/util/util';
import { Gender, PaginationResponse, PartnerUserRole } from '@/types/types';
import { PartnerUser } from '@/types/models';

interface Props {
  partnerUsers: PaginationResponse<PartnerUser>;
}

export default function ListPartnerUsers({ partnerUsers }: Props) {
  const { handleResponseToast } = useMyToast();
  const [isCreateFormOpen, setIsCreateFormOpen] = useState(false);
  const form = useWebForm({
    first_name: '',
    last_name: '',
    other_names: '',
    username: '',
    phone: '',
    email: '',
    gender: '',
    password: '',
    password_confirmation: '',
    role: PartnerUserRole.Staff,
  });

  const submit = async () => {
    const res = await form.submit((data, web) =>
      web.post(route('managers.partner-users.store'), data)
    );
    if (!handleResponseToast(res)) {
      return;
    }
    form.reset();
    Inertia.reload();
  };

  const headers: ServerPaginatedTableHeader<PartnerUser>[] = [
    {
      label: 'Name',
      render: (row) => row.user?.full_name ?? '',
    },
    {
      label: 'Email',
      render: (row) => row.user?.email ?? '',
    },
    {
      label: 'Phone',
      render: (row) => row.user?.phone ?? '',
    },
    {
      label: 'Role',
      render: (row) => <PartnerUserRoleEditor partnerUser={row} />,
    },
  ];

  return (
    <ManagerDashboardLayout>
      <VStack align="stretch" spacing={4}>
        <Slab>
          <SlabHeading
            title="Register Partner User"
            rightElement={
              <Button
                colorScheme="brand"
                size="sm"
                variant="outline"
                onClick={() => setIsCreateFormOpen((value) => !value)}
              >
                {isCreateFormOpen ? 'Hide Form' : 'New User'}
              </Button>
            }
          />
          {isCreateFormOpen && (
            <SlabBody>
              <Div as="form" onSubmit={preventNativeSubmit(submit)} p={6}>
                <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                  <FormControlBox
                    form={form}
                    title="First Name"
                    formKey="first_name"
                  >
                    <Input
                      value={form.data.first_name}
                      onChange={(e) =>
                        form.setValue('first_name', e.currentTarget.value)
                      }
                      required
                    />
                  </FormControlBox>
                  <FormControlBox
                    form={form}
                    title="Last Name"
                    formKey="last_name"
                  >
                    <Input
                      value={form.data.last_name}
                      onChange={(e) =>
                        form.setValue('last_name', e.currentTarget.value)
                      }
                      required
                    />
                  </FormControlBox>
                  <FormControlBox
                    form={form}
                    title="Other Names"
                    formKey="other_names"
                  >
                    <Input
                      value={form.data.other_names}
                      onChange={(e) =>
                        form.setValue('other_names', e.currentTarget.value)
                      }
                    />
                  </FormControlBox>
                  <FormControlBox form={form} title="Phone" formKey="phone">
                    <Input
                      type="tel"
                      value={form.data.phone}
                      onChange={(e) =>
                        form.setValue('phone', e.currentTarget.value)
                      }
                    />
                  </FormControlBox>
                  <FormControlBox form={form} title="Email" formKey="email">
                    <Input
                      type="email"
                      value={form.data.email}
                      onChange={(e) =>
                        form.setValue('email', e.currentTarget.value)
                      }
                      required
                    />
                  </FormControlBox>
                  <FormControlBox
                    form={form}
                    title="Username"
                    formKey="username"
                  >
                    <Input
                      value={form.data.username}
                      onChange={(e) =>
                        form.setValue('username', e.currentTarget.value)
                      }
                      required
                    />
                  </FormControlBox>
                  <FormControlBox form={form} title="Gender" formKey="gender">
                    <EnumSelect
                      enumData={Gender}
                      selectValue={form.data.gender}
                      onChange={(e: any) => form.setValue('gender', e.value)}
                    />
                  </FormControlBox>
                  <FormControlBox
                    form={form}
                    title="Partner Role"
                    formKey="role"
                  >
                    <EnumSelect
                      enumData={PartnerUserRole}
                      selectValue={form.data.role}
                      onChange={(e: any) => form.setValue('role', e.value)}
                      required
                    />
                  </FormControlBox>
                  <FormControlBox
                    form={form}
                    title="Password"
                    formKey="password"
                  >
                    <Input
                      type="password"
                      value={form.data.password}
                      onChange={(e) =>
                        form.setValue('password', e.currentTarget.value)
                      }
                      required
                    />
                  </FormControlBox>
                  <FormControlBox
                    form={form}
                    title="Confirm Password"
                    formKey="password_confirmation"
                  >
                    <Input
                      type="password"
                      value={form.data.password_confirmation}
                      onChange={(e) =>
                        form.setValue(
                          'password_confirmation',
                          e.currentTarget.value
                        )
                      }
                      required
                    />
                  </FormControlBox>
                </SimpleGrid>
                <FormButton title="Register User" isLoading={form.processing} />
              </Div>
            </SlabBody>
          )}
        </Slab>

        <Slab>
          <SlabHeading title="Partner Users" />
          <SlabBody>
            <ServerPaginatedTable
              scroll={true}
              headers={headers}
              data={partnerUsers.data}
              keyExtractor={(row) => row.id}
              paginator={partnerUsers}
            />
          </SlabBody>
        </Slab>
      </VStack>
    </ManagerDashboardLayout>
  );
}

function PartnerUserRoleEditor({ partnerUser }: { partnerUser: PartnerUser }) {
  const [role, setRole] = useState(partnerUser.role);
  const form = useWebForm({ role: partnerUser.role });
  const { handleResponseToast } = useMyToast();

  async function updateRole() {
    form.setValue('role', role);
    const res = await form.submit((data, web) =>
      web.post(route('managers.partner-users.update', [partnerUser.id]), {
        ...data,
        role,
      })
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  return (
    <HStack minW="220px">
      <Div flex={1}>
        <EnumSelect
          enumData={PartnerUserRole}
          selectValue={role}
          onChange={(e: any) => setRole(e.value)}
        />
      </Div>
      <Button
        colorScheme="brand"
        size="sm"
        onClick={updateRole}
        isLoading={form.processing}
        isDisabled={role === partnerUser.role}
      >
        Save
      </Button>
    </HStack>
  );
}
