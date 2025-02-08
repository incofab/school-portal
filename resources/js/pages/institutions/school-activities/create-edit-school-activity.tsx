import React from 'react';
import { FormControl, Textarea, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SchoolActivity } from '@/types/models';
import { Inertia } from '@inertiajs/inertia';
import FormControlBox from '@/components/forms/form-control-box';

interface Props {
  schoolActivity?: SchoolActivity;
}

export default function CreateSchoolActivity({ schoolActivity }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    title: schoolActivity?.title ?? '',
    description: schoolActivity?.description ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      schoolActivity
        ? web.put(instRoute('school-activities.update', [schoolActivity]), data)
        : web.post(instRoute('school-activities.store'), data)
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('school-activities.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Add Activity`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm form={webForm as any} formKey="title" title="Title" />

              <FormControlBox
                form={webForm as any}
                formKey="description"
                title="Description"
              >
                <Textarea
                  value={webForm.data.description}
                  onChange={(e) =>
                    webForm.setValue('description', e.currentTarget.value)
                  }
                />
              </FormControlBox>

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
