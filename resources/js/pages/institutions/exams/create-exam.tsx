import React, { useState } from 'react';
import { Checkbox, FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Event, EventCourseable, Exam } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  event: Event;
  external_reference: string;
}

export default function CreateExam({ event, external_reference }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [selectedEventCourseables, setSelectedEventCourseables] = useState<{
    [key: number]: EventCourseable;
  }>();

  const webForm = useWebForm({
    external_reference: 'external_reference',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('exams.store', [event.id]), {
        ...data,
        courseables: Object.entries(selectedEventCourseables ?? {}).map(
          ([key, eventCourseable]) => ({
            courseable_type: eventCourseable.courseable_type,
            courseable_id: eventCourseable.courseable_id,
          })
        ),
      });
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('display-exam-page', [res.data.exam.exam_no]));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Start Exam`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
              align={'stretch'}
            >
              {event.event_courseables?.map((eventCourseable) => (
                <Checkbox
                  key={eventCourseable.id}
                  checked={
                    selectedEventCourseables &&
                    Boolean(selectedEventCourseables[eventCourseable.id])
                  }
                  onChange={(e) =>
                    setSelectedEventCourseables({
                      ...selectedEventCourseables,
                      [eventCourseable.id]: eventCourseable,
                    })
                  }
                >{`${eventCourseable.courseable?.course?.title} - ${eventCourseable.courseable?.session}`}</Checkbox>
              ))}
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
