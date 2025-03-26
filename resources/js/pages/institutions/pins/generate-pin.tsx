import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Textarea, VStack } from '@chakra-ui/react';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import InputForm from '@/components/forms/input-form';
import { FormControlButton } from '@/components/buttons';
import { Inertia } from '@inertiajs/inertia';
import CenteredBox from '@/components/centered-box';
import DashboardLayout from '@/layout/dashboard-layout';
import useInstitutionRoute from '@/hooks/use-institution-route';

export default function GeneratePin() {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    comment: '',
    num_of_pins: '',
    reference: Math.random() + ' - ' + generateRandomString(12),
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('pin-generators.store'), data)
    );

    if (!handleResponseToast(res)) return;

    Inertia.replace(
      instRoute('pin-generators.show', [res.data.pinGenerator.id])
    );
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title="Generate Result Pins" />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={webForm as any}
                formKey="num_of_pins"
                title="Number of Pins"
                type="number"
                isRequired
              />
              <FormControlBox
                formKey="comment"
                title="Comment"
                form={webForm as any}
              >
                <Textarea
                  value={webForm.data.comment}
                  onChange={(e: any) =>
                    webForm.setValue('comment', e.currentTarget.value)
                  }
                />
              </FormControlBox>
              <FormControlButton isLoading={webForm.processing} />
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
