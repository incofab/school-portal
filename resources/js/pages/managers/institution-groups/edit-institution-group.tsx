import React from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { InstitutionGroup } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';

interface Props {
  institutionGroup?: InstitutionGroup;
}

export default function UpdateInstitutionGroup({ institutionGroup }: Props) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    name: institutionGroup?.name ?? '',
    loan_limit: institutionGroup?.loan_limit ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        ...data,
      };
      return institutionGroup
        ? web.put(
            route('managers.institution-groups.update', [institutionGroup]),
            postData
          )
        : web.post(route('managers.institution-groups.store'), postData);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(route('managers.institution-groups.index'));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${institutionGroup ? 'Update' : 'Create'} Group`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={webForm as any}
                formKey="name"
                title="Institution Group Name"
              />

              <InputForm
                form={webForm as any}
                formKey="loan_limit"
                title="Loan Limit"
              />

              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
