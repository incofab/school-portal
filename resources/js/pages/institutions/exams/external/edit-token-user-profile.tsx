import React from 'react';
import { TokenUser } from '@/types/models';
import ExamLayout from '../exam-layout';
import CenteredBox from '@/components/centered-box';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormControl, VStack } from '@chakra-ui/react';
import { preventNativeSubmit } from '@/util/util';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useWebForm from '@/hooks/use-web-form';
import { FormButton } from '@/components/buttons';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  tokenUser: TokenUser;
}

export default function EditTokenUserProfile({ tokenUser }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    name: tokenUser.name ?? '',
    phone: tokenUser.phone ?? '',
    email: tokenUser.email ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.put(instRoute('external.token-users.update', [tokenUser]), data)
    );

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('external.home'));
  };

  return (
    <ExamLayout
      title={'Update your profile'}
      rightElement={tokenUser.name}
      breadCrumbItems={[{ title: 'Edit Profile' }]}
      examable={tokenUser}
    >
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Update Profile`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
              align={'stretch'}
            >
              <VStack spacing={2}>
                <InputForm
                  form={webForm as any}
                  formKey="name"
                  title="Your Name"
                />
                <InputForm
                  form={webForm as any}
                  formKey="phone"
                  title="Phone No"
                  type={'tel'}
                />
                <InputForm
                  form={webForm as any}
                  formKey="email"
                  title="Email"
                  type="email"
                />
                <FormControl>
                  <FormButton isLoading={webForm.processing} />
                </FormControl>
              </VStack>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ExamLayout>
  );
}
