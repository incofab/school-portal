import React from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import FormControlBox from '@/components/forms/form-control-box';
import InstitutionGroupSelect from '@/components/selectors/institution-group-select';
import { InstitutionGroup } from '@/types/models';

interface Props {
  institutionGroups: InstitutionGroup[];
}

export default function UpdateInstitutionGroup({ institutionGroups }: Props) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    institution_group_id: '',
    name: '',
    phone: '',
    email: '',
    address: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(route('managers.institutions.store'), data);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(route('managers.institutions.index'));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Create Institution`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                form={webForm}
                formKey={'institution_group_id'}
                title="Institution Group"
              >
                <InstitutionGroupSelect
                  institutionGroups={institutionGroups}
                  selectValue={webForm.data.institution_group_id}
                  onChange={(e: any) =>
                    webForm.setValue('institution_group_id', e.value)
                  }
                />
              </FormControlBox>
              <InputForm form={webForm as any} formKey="name" title="Name" />
              <InputForm form={webForm as any} formKey="email" title="Email" />
              <InputForm form={webForm as any} formKey="phone" title="Phone" />
              <InputForm
                form={webForm as any}
                formKey="address"
                title="Address"
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
