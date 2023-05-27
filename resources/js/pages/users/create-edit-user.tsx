import React from 'react';
import { AxiosInstance } from 'axios';
import '../../../css/dashboard.css';
import { FormControl, VStack, useToast } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import { Inertia } from '@inertiajs/inertia';
import UserInputForm from '@/components/user-input-form';
import { Student, User } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';

interface Props {
  user?: User & {
    student?: Student;
  };
}

function CreateOrUpdateUser({ user }: Props) {
  const toast = useToast();
  const webForm = useWebForm({
    ...(user ?? {
      first_name: '',
      last_name: '',
      other_names: '',
      email: '',
      phone: '',
      role: '',
      is_welfare: false,
    }),
    ...(user?.student ? user.student : {}),
  });

  const submit = async () => {
    const { ok, message } = await webForm.submit((data, web: AxiosInstance) =>
      user
        ? web.put(route('users.update', [user]), data)
        : web.post(route('users.store'), data)
    );
    if (!ok) {
      return void toast({
        title: message ?? 'Error process user record',
        status: 'error',
      });
    }
    toast({
      title: 'User record processed successfully',
      status: 'success',
    });

    Inertia.visit(route('users.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab w={'full'}>
          <SlabHeading title={`${user ? 'Update' : 'Create'} user record`} />
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

export default CreateOrUpdateUser;
