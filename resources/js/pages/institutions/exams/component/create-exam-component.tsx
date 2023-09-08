import React, { useState } from 'react';
import { Checkbox, FormControl, Spacer, Text, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Event, EventCourseable, TokenUser } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Div } from '@/components/semantic';
import { LabelText } from '@/components/result-helper-components';

interface Props {
  event: Event;
  tokenUser: TokenUser;
}

export default function CreateExamComponent({ event, tokenUser }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [selectedEventCourseables, setSelectedEventCourseables] = useState<{
    [key: number]: EventCourseable;
  }>();

  const webForm = useWebForm({
    external_reference: tokenUser.reference,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('external.exams.store', [event.id]), {
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
    <CenteredBox>
      <Slab>
        <SlabHeading title={`Select Exam Subjects`} />
        <SlabBody>
          <Div>
            <VStack align={'stretch'} spacing={1}>
              <LabelText label="Event" text={event.title} />
              <LabelText label="Duration" text={event.duration + 'mins'} />
              <LabelText label="Num of Subjects" text={event.num_of_subjects} />
            </VStack>
            <Spacer height={'20px'} />
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
          </Div>
        </SlabBody>
      </Slab>
    </CenteredBox>
  );
}
