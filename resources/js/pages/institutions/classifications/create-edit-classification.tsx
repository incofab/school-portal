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
import FormControlBox from '@/components/forms/form-control-box';
import StaffSelect from '@/components/selectors/staff-select';
import { InstitutionUserType } from '@/types/types';

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
    form_teacher_id: classification?.form_teacher
      ? {
          label: classification.form_teacher.full_name,
          value: classification.form_teacher_id,
        }
      : null,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        ...data,
        form_teacher_id: data.form_teacher_id?.value,
      };
      return classification
        ? web.put(
            instRoute('classifications.update', [classification]),
            postData
          )
        : web.post(instRoute('classifications.store'), postData);
    });
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

              <FormControlBox
                title="Form Teacher"
                form={webForm as any}
                formKey="form_teacher_id"
              >
                <StaffSelect
                  value={webForm.data.form_teacher_id}
                  isClearable={true}
                  rolesIn={[InstitutionUserType.Teacher]}
                  onChange={(e) => webForm.setValue('form_teacher_id', e)}
                  isMulti={false}
                />
              </FormControlBox>

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
