import React from 'react';
import { Event, Exam } from '@/types/models';
import { VStack, Text, Divider, HStack, Spacer } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { ExamStatus, SelectOptionType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { dateFormat, isTimeExpired } from '@/util/util';
import useIsStudent from '@/hooks/use-is-student';
import { format } from 'date-fns';
import Dt from '@/components/dt';
import { Div } from '@/components/semantic';

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
            <HStack>
              {studentExam ? (
                <>
                  {studentExam.status === ExamStatus.Ended ? (
                    <LinkButton
                      href={instRoute('external.exam-result', [
                        studentExam.exam_no,
                      ])}
                      title={'View Result'}
                    />
                  ) : (
                    <LinkButton
                      href={instRoute('display-exam-page', [
                        studentExam.exam_no,
                      ])}
                      title={'Continue'}
                    />
                  )}
                </>
              ) : (
                <></>
              )}
              {canStart() ? (
                <LinkButton
                  href={instRoute('exams.create', [event])}
                  title={'Start'}
                />
              ) : null}
            </HStack>
          }
        />
        <SlabBody>
          <VStack align={'stretch'}>
            <Dt contentData={eventDetails} labelWidth={150} />
            <br />
            <Divider my={4} />
            <br />
            <Text fontWeight={'bold'}>Subjects</Text>
            {studentExam ? (
              <ShowExam exam={studentExam} />
            ) : (
              <>
                {event.event_courseables?.map((eventCoursable) => (
                  <Text py={2} key={eventCoursable.courseable_id}>
                    {eventCoursable.courseable?.course?.title}
                  </Text>
                ))}
              </>
            )}
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function ShowExam({ exam }: { exam: Exam }) {
  const { instRoute } = useInstitutionRoute();
  return (
    <Div>
      {exam.exam_courseables?.map((examCourseable) => {
        return (
          <HStack key={examCourseable.id} align={'stretch'}>
            <Text py={2} key={examCourseable.courseable_id}>
              {examCourseable.courseable?.course?.title}
            </Text>
            <Spacer />
            {exam.status === ExamStatus.Ended && (
              <LinkButton
                href={instRoute('exam-courseables.show', [
                  examCourseable.exam_id,
                  examCourseable.id,
                ])}
                title="View Details"
              />
            )}
          </HStack>
        );
      })}
    </Div>
  );
}
