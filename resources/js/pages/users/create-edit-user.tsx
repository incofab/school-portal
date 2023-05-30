import React from 'react';
import { AxiosInstance } from 'axios';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import UserInputForm from '@/components/user-input-form';
import { InstitutionUser, User } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  user?: User & {
    institution_user: InstitutionUser;
  };
}

export function CreateOrUpdateStaff({ user }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    first_name: user?.first_name ?? '',
    last_name: user?.last_name ?? '',
    other_names: user?.other_names ?? '',
    email: user?.email ?? '',
    phone: user?.phone ?? '',
    role: user?.institution_user.role ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) =>
      user
        ? web.put(instRoute('staff.update', [user]), data)
        : web.post(instRoute('staff.store'), data)
    );
    handleResponseToast(res);
    Inertia.visit(instRoute('users.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab w={'full'}>
          <SlabHeading title={`${user ? 'Update' : 'Create'} User Record`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <UserInputForm webForm={webForm} />
              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
