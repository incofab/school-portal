import React from 'react';
import { FormControl, Input, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Event } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import format from 'date-fns/format';

interface Props {
  event?: Event;
}

export default function CreateOrUpdateEvent({ event }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: event?.title ?? '',
    description: event?.description ?? '',
    duration: event?.duration ?? '',
    status: event?.status ?? '',
    starts_at: event?.starts_at ?? '',
    num_of_subjects: event?.num_of_subjects ?? 1,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return event
        ? web.put(instRoute('events.update', [event]), data)
        : web.post(instRoute('events.store'), data);
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('events.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`${event ? 'Update' : 'Create'} Event`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={webForm as any}
                formKey="title"
                title="Event title"
                isRequired
              />
              <InputForm
                form={webForm as any}
                formKey="description"
                title="Description [optional]"
              />
              <InputForm
                form={webForm as any}
                formKey="duration"
                title="Duration [mins]"
                isRequired
              />
              <InputForm
                form={webForm as any}
                formKey="num_of_subjects"
                title="Num of Subjects"
                type="number"
                min={1}
                max={4}
                isRequired
              />
              <FormControlBox
                title="Start time"
                form={webForm as any}
                formKey="starts_at"
                isRequired
              >
                <Input
                  type={'datetime-local'}
                  max={'9999-12-31'}
                  value={webForm.data.starts_at ?? ''}
                  onChange={(e) =>
                    webForm.setValue(
                      'starts_at',
                      format(
                        new Date(e.currentTarget.value),
                        'yyyy-MM-dd HH:mm:ss'
                      )
                    )
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
