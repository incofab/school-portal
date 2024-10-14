import React from 'react';
import { FormControl, Input, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { dateTimeFormat, preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { ClassificationGroup, Event } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import format from 'date-fns/format';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';

interface Props {
  event?: Event;
  classificationGroups: ClassificationGroup[];
}

export default function CreateOrUpdateEvent({
  event,
  classificationGroups,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: event?.title ?? '',
    description: event?.description ?? '',
    duration: event?.duration ?? '',
    status: event?.status ?? '',
    starts_at: event?.starts_at ?? '',
    num_of_subjects: event?.num_of_subjects ?? 1,
    classification_id: event?.classification_id ?? '',
    classification_group_id: event?.classification_group_id ?? '',
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
                  value={
                    webForm.data.starts_at
                      ? format(new Date(webForm.data.starts_at), dateTimeFormat)
                      : ''
                  }
                  onChange={(e) =>
                    webForm.setValue(
                      'starts_at',
                      format(new Date(e.currentTarget.value), dateTimeFormat)
                    )
                  }
                />
              </FormControlBox>
              <FormControlBox
                title="Class Group"
                form={webForm as any}
                formKey="classification_group_id"
              >
                <ClassificationGroupSelect
                  selectValue={webForm.data.classification_group_id}
                  isMulti={false}
                  isClearable={true}
                  classificationGroups={classificationGroups}
                  onChange={(e: any) =>
                    webForm.setData({
                      ...webForm.data,
                      classification_group_id: e?.value,
                      classification_id: '',
                    })
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
