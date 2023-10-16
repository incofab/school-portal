import React from 'react';
import { Checkbox, FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { ClassificationGroup } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import StaffSelect from '@/components/selectors/staff-select';
import { InstitutionUserType } from '@/types/types';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';

interface Props {
  classificationGroup?: ClassificationGroup;
}

export default function CreateOrUpdateClassification({
  classificationGroup,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: classificationGroup?.title ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        ...data,
      };
      return classificationGroup
        ? web.put(
            instRoute('classification-groups.update', [classificationGroup]),
            postData
          )
        : web.post(instRoute('classification-groups.store'), postData);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('classification-groups.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${classificationGroup ? 'Update' : 'Create'} Class Group`}
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
                title="Class Group Title"
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
