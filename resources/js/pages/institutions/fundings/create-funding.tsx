import React from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { generateRandomString } from '@/util/util';
import useSharedProps from '@/hooks/use-shared-props';

export default function CreateFunding() {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentInstitution } = useSharedProps();
  const webForm = useWebForm({
    amount: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      // web.post(instRoute('fundings.store'), data)
      web.post(instRoute('fundings.store'), {
        ...data,
        reference: `${currentInstitution.id}-${generateRandomString(16)}`,
      })
    );

    if (!handleResponseToast(res)) return;
    // Inertia.visit(instRoute('fundings.index'));
    window.location.href = res.data.authorization_url;
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Add Fund`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={webForm as any}
                formKey="amount"
                title="Amount"
              />

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
