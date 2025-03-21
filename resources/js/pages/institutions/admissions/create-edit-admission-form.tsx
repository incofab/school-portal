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
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { TermType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  admissionForm?: AdmissionForm;
}

export default function CreateEditAdmissionForms({ admissionForm }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentTerm, currentAcademicSession } = useSharedProps();

  const webForm = useWebForm({
    title: admissionForm?.title ?? '',
    description: admissionForm?.description ?? '',
    price: admissionForm?.price ?? '',
    is_published: admissionForm?.is_published ?? false,
    academic_session_id:
      admissionForm?.academic_session_id ?? currentAcademicSession.id,
    term: admissionForm?.term ?? currentTerm,
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
            <FormControlBox
              form={webForm as any}
              title="Academic Session [Optional]"
              formKey="academic_session_id"
            >
              <AcademicSessionSelect
                selectValue={webForm.data.academic_session_id}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) =>
                  webForm.setValue('academic_session_id', e?.value)
                }
              />
            </FormControlBox>
            <FormControlBox form={webForm as any} title="Term" formKey="term">
              <EnumSelect
                enumData={TermType}
                selectValue={webForm.data.term}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('term', e?.value)}
                required
              />
            </FormControlBox>
            <FormControlBox
              form={webForm as any}
              formKey="is_published"
              title=""
            >
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
