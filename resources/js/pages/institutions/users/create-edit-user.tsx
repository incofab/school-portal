import React from 'react';
import { AxiosInstance } from 'axios';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import UserInputForm from '@/components/user-input-form';
import { InstitutionUser } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  institutionUser?: InstitutionUser;
}

export default function CreateOrUpdateStudent({ institutionUser }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    first_name: institutionUser?.user!.first_name ?? '',
    last_name: institutionUser?.user!.last_name ?? '',
    other_names: institutionUser?.user!.other_names ?? '',
    email: institutionUser?.user!.email ?? '',
    phone: institutionUser?.user!.phone ?? '',
    role: institutionUser?.role ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) =>
      institutionUser
        ? web.put(instRoute('users.update', [institutionUser]), data)
        : web.post(instRoute('users.store'), {
            ...data,
            password: 'password',
            password_confirmation: 'password',
          })
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('users.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab w={'full'}>
          <SlabHeading
            title={`${institutionUser ? 'Update' : 'Create'} User Record`}
          />
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
