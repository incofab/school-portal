import React from 'react';
import { Event, Exam } from '@/types/models';
import { VStack, Text, Divider } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { SelectOptionType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { dateFormat, isTimeExpired } from '@/util/util';
import useIsStudent from '@/hooks/use-is-student';
import { format } from 'date-fns';
import Dt from '@/components/dt';

interface Props {
  event: Event;
  studentExam?: Exam;
}

export default function ShowEvent({ event, studentExam }: Props) {
  const { instRoute } = useInstitutionRoute();
  const isStudent = useIsStudent();

  const eventDetails: SelectOptionType[] = [
    { label: 'Title', value: event.title },
    { label: 'Duration', value: String(event.duration) },
    {
      label: 'Starts At',
      value: event.friendly_start_date,
    },
    {
      label: 'Expires At',
      value: event.expires_at
        ? ''
        : format(new Date(event.expires_at!), dateFormat),
    },
    { label: 'Num of Subject', value: String(event.num_of_subjects) },
    { label: 'Class', value: String(event.classification_group?.title ?? '') },
  ];

  function canStart() {
    if (!isStudent) {
      return false;
    }
    if (isTimeExpired(event.expires_at)) {
      return false;
    }
    if (!isTimeExpired(event.starts_at)) {
      return false;
    }
    return studentExam == undefined;
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Event Details"
          rightElement={
            <>
              {canStart() ? (
                <LinkButton
                  href={instRoute('exams.create', [event])}
                  disabled={!canStart()}
                  title={'Start'}
                />
              ) : null}
            </>
          }
        />
        <SlabBody>
          <VStack align={'stretch'}>
            <Dt contentData={eventDetails} labelWidth={150} />
            <br />
            <Divider my={4} />
            <br />
            <Text fontWeight={'bold'}>Subjects</Text>
            {event.event_courseables?.map((eventCoursable) => (
              <Text py={2} key={eventCoursable.courseable_id}>
                {eventCoursable.courseable?.course?.title}
              </Text>
            ))}
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
