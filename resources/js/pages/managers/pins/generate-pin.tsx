import React from 'react';
import { Nullable, SelectOptionType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { VStack } from '@chakra-ui/react';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import FormControlBox from '@/components/forms/form-control-box';
import InstitutionSelect from '@/components/selectors/institution-select';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import InputForm from '@/components/forms/input-form';
import { FormControlButton } from '@/components/buttons';
import route from '@/util/route';
import { Inertia } from '@inertiajs/inertia';
import CenteredBox from '@/components/centered-box';

export default function GeneratePin() {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    institution_id: {} as Nullable<SelectOptionType<number>>,
    num_of_pins: '',
    reference: Math.random() + ' - ' + generateRandomString(12),
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(route('managers.generate-pin.store'), {
        ...data,
        institution_id: data.institution_id?.value,
      })
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(route('managers.pins.index', [res.data.pinGenerator]));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title="Result Pins" />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                isRequired
                formKey="institution_id"
                title="Institution"
                form={webForm as any}
              >
                <InstitutionSelect
                  value={webForm.data.institution_id}
                  onChange={(e: any) => webForm.setValue('institution_id', e)}
                />
              </FormControlBox>
              <InputForm
                form={webForm as any}
                formKey="num_of_pins"
                title="Number of Pins"
                type="number"
                isRequired
              />
              <FormControlButton isLoading={webForm.processing} />
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
