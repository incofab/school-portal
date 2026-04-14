import React from 'react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import CenteredBox from '@/components/centered-box';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { AcademicSession } from '@/types/models';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import route from '@/util/route';
import { FormControl, VStack } from '@chakra-ui/react';
import { preventNativeSubmit } from '@/util/util';
import InputForm from '@/components/forms/input-form';
import { FormButton } from '@/components/buttons';

interface Props {
  academicSession?: AcademicSession;
}

export default function CreateEditAcademicSession({ academicSession }: Props) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    title: academicSession?.title ?? '',
    order_index: String(academicSession?.order_index ?? 100),
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return academicSession
        ? web.put(
            route('managers.academic-sessions.update', [academicSession]),
            {
              ...data,
              order_index: Number(data.order_index),
            }
          )
        : web.post(route('managers.academic-sessions.store'), {
            ...data,
            order_index: Number(data.order_index),
          });
    });

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.visit(route('managers.academic-sessions.index'));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${academicSession ? 'Update' : 'Create'} Academic Session`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as="form"
              onSubmit={preventNativeSubmit(submit)}
              align="stretch"
            >
              <InputForm form={webForm as any} formKey="title" title="Title" />
              <InputForm
                form={webForm as any}
                formKey="order_index"
                title="Order Index"
                type="number"
              />
              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
