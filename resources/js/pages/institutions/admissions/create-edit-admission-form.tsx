import React from 'react';
import { Checkbox, FormControl, Textarea, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { AdmissionForm } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import InputForm from '@/components/forms/input-form';

interface Props {
  admissionForm?: AdmissionForm;
}

export default function CreateEditAdmissionForms({ admissionForm }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    title: admissionForm?.title ?? '',
    description: admissionForm?.description ?? '',
    price: admissionForm?.price ?? '',
    is_published: admissionForm?.is_published ?? false,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      admissionForm
        ? web.put(instRoute('admission-forms.update', [admissionForm]), data)
        : web.post(instRoute('admission-forms.store'), data)
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('admission-forms.index'));
  };
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`${admissionForm ? 'Update' : 'Record'} Admission form`}
        />
        <SlabBody>
          <VStack
            spacing={4}
            as={'form'}
            onSubmit={preventNativeSubmit(submit)}
          >
            <InputForm form={webForm as any} formKey="title" title="Title" />
            <FormControlBox
              form={webForm as any}
              title="Description [Optional]"
              formKey="description"
            >
              <Textarea
                onChange={(e) =>
                  webForm.setValue('description', e.currentTarget.value)
                }
                value={webForm.data.description}
              />
            </FormControlBox>
            <InputForm form={webForm as any} formKey="price" title="Price" />
            <FormControlBox form={webForm as any} formKey="forMidTerm" title="">
              <Checkbox
                isChecked={webForm.data.is_published}
                onChange={(e) =>
                  webForm.setValue('is_published', e.currentTarget.checked)
                }
              >
                Publish (Can candidates start applying)
              </Checkbox>
            </FormControlBox>
            <FormControl>
              <FormButton isLoading={webForm.processing} />
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
