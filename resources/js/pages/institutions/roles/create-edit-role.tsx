import React, { useMemo } from 'react';
import {
  Checkbox,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Heading,
  Input,
  SimpleGrid,
  Text,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import { AxiosInstance } from 'axios';
import { Inertia } from '@inertiajs/inertia';
import DashboardLayout from '@/layout/dashboard-layout';
import CenteredBox from '@/components/centered-box';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FormControlBox from '@/components/forms/form-control-box';
import { FormButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Permission, Role } from '@/types/models';
import { preventNativeSubmit } from '@/util/util';
import startCase from 'lodash/startCase';

interface Props {
  role?: Role;
  permissions: Permission[];
}

export default function CreateEditRole({ role, permissions }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    name: role?.name ?? '',
    description: role?.description ?? '',
    permissions: role?.permissions?.map((permission) => permission.name) ?? [],
  });

  const groupedPermissions = useMemo(
    () =>
      permissions.reduce<Record<string, Permission[]>>((groups, permission) => {
        const group = permission.name.split('-')[0] ?? 'other';
        groups[group] = [...(groups[group] ?? []), permission];
        return groups;
      }, {}),
    [permissions]
  );

  const togglePermission = (permission: string, checked: boolean) => {
    const selected = new Set(form.data.permissions);
    checked ? selected.add(permission) : selected.delete(permission);
    form.setValue('permissions', Array.from(selected));
  };

  const submit = async () => {
    const response = await form.submit((data, web: AxiosInstance) =>
      role
        ? web.put(instRoute('roles.update', [role]), data)
        : web.post(instRoute('roles.store'), data)
    );
    if (!handleResponseToast(response)) return;
    Inertia.visit(instRoute('roles.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab w="full">
          <SlabHeading title={`${role ? 'Edit' : 'Create'} role`} />
          <SlabBody>
            <VStack
              as="form"
              align="stretch"
              spacing={5}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                form={form as any}
                title="Role name"
                formKey="name"
              >
                <Input
                  value={form.data.name}
                  onChange={(event) =>
                    form.setValue('name', event.currentTarget.value)
                  }
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={form as any}
                title="Description"
                formKey="description"
              >
                <Textarea
                  value={form.data.description}
                  onChange={(event) =>
                    form.setValue('description', event.currentTarget.value)
                  }
                  placeholder="Explain what this role is responsible for"
                  rows={3}
                />
              </FormControlBox>

              <FormControl isInvalid={!!form.errors.permissions}>
                <FormLabel>Permissions</FormLabel>
                <Text fontSize="sm" color="gray.600" mb={3}>
                  Select only the access this role needs. You can change these
                  permissions later.
                </Text>
                <VStack align="stretch" spacing={4}>
                  {Object.entries(groupedPermissions).map(
                    ([group, groupPermissions]) => (
                      <VStack
                        key={group}
                        align="stretch"
                        borderWidth="1px"
                        borderRadius="md"
                        p={{ base: 3, md: 4 }}
                        spacing={2}
                      >
                        <Heading size="sm">{startCase(group)}</Heading>
                        <SimpleGrid columns={{ base: 1, md: 2 }} spacing={2}>
                          {groupPermissions.map((permission) => (
                            <Checkbox
                              key={permission.name}
                              isChecked={form.data.permissions.includes(
                                permission.name
                              )}
                              onChange={(event) =>
                                togglePermission(
                                  permission.name,
                                  event.target.checked
                                )
                              }
                            >
                              {startCase(permission.name.replaceAll('-', ' '))}
                            </Checkbox>
                          ))}
                        </SimpleGrid>
                      </VStack>
                    )
                  )}
                </VStack>
                <FormErrorMessage>{form.errors.permissions}</FormErrorMessage>
              </FormControl>
              <FormControl>
                <FormButton
                  title={role ? 'Update role' : 'Create role'}
                  isLoading={form.processing}
                />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
