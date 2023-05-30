import React from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Classification } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton, LinkButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  classification?: Classification;
}

export default function CreateOrUpdateClassification({
  classification,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: classification?.title ?? '',
    description: classification?.description ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      classification
        ? web.put(instRoute('classifications.update', [classification]), data)
        : web.post(instRoute('classifications.store'), data)
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('classifications.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${classification ? 'Update' : 'Create'} Class`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={webForm as any}
                formKey="title"
                title="Class title"
              />

              <InputForm
                form={webForm as any}
                formKey="description"
                title="Description [optional]"
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
