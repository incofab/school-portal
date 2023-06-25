import React from 'react';
import { Checkbox, FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Classification } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
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
    has_equal_subjects: classification?.has_equal_subjects ?? true,
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
                <Checkbox
                  isChecked={webForm.data.has_equal_subjects}
                  onChange={(e) =>
                    webForm.setValue(
                      'has_equal_subjects',
                      e.currentTarget.checked
                    )
                  }
                  size={'md'}
                  colorScheme="brand"
                >
                  All students offer the number of subjects
                </Checkbox>
              </FormControl>

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
