import React from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Course } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton, LinkButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  course?: Course;
}

export default function CreateOrUpdateCourse({ course }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: course?.title ?? '',
    description: course?.description ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const dataWithCode = { ...data, code: data.title };
      return course
        ? web.put(instRoute('courses.update', [course]), dataWithCode)
        : web.post(instRoute('courses.store'), dataWithCode);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('courses.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${course ? 'Update' : 'Create'} Subject`}
            rightElement={
              <LinkButton
                href={instRoute('courses.multi-create')}
                title={'Multi Create'}
              />
            }
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
                title="Subject title"
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
