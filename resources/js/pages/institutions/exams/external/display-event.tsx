import { Divider, HStack, Icon, Spacer, Text, VStack } from '@chakra-ui/react';
import React, { useEffect, useState } from 'react';
import { Event, TokenUser } from '@/types/models';
import { Div } from '@/components/semantic';
import ExamLayout from '../exam-layout';
import CenteredBox from '@/components/centered-box';
import { format, intervalToDuration } from 'date-fns';
import { AcademicCapIcon } from '@heroicons/react/24/solid';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { EventStatus, ExamStatus } from '@/types/types';
import { dateTimeFormat } from '@/util/util';

interface Props {
  event: Event;
  tokenUser: TokenUser;
}

export default function DisplayEvent({ event, tokenUser }: Props) {
  const { instRoute } = useInstitutionRoute();
  const [canTakeExam, setCanTakeExam] = useState(false);
  const duration = intervalToDuration({
    start: 0,
    end: event.duration * 60 * 1000,
  });

  function ActionButton() {
    if (event.status !== EventStatus.Active) {
      return null;
    }
    const myExam = event.exams?.find(
      (item) => item.external_reference === tokenUser.reference
    );

    if (!myExam) {
      return (
        <LinkButton
          href={instRoute('external.exams.create', [event])}
          title="Take Exam"
        />
      );
    }

    if (myExam.status !== ExamStatus.Ended) {
      return null;
    }

    return (
      <LinkButton
        href={instRoute('display-exam-page', [myExam.exam_no])}
        title="Continue Exam"
      />
    );
  }

  return (
    <ExamLayout
      title={event.title}
      rightElement={<Text>{tokenUser.name}</Text>}
    >
      <CenteredBox>
        <Div textAlign={'center'}>
          <Text fontSize={'3xl'} color={'brand.700'} fontWeight={'semibold'}>
            {event.title}
          </Text>
          <Text fontSize={'2xl'} color={'brand.900'} fontWeight={'semibold'}>
            Starts on {format(new Date(event.starts_at), 'PPPPpp')}
          </Text>
          <Icon as={AcademicCapIcon} color={'brand.600'} fontSize={'9xl'} />
          <Div display={canTakeExam ? 'none' : undefined}>
            <CountDown
              startDate={new Date(event.starts_at)}
              onTimeElapsed={() => setCanTakeExam(true)}
            />
          </Div>
          <br />
          <HStack>
            <Div>
              <Text as={'span'}>Duration: </Text>
              <Text as={'span'}>
                {duration.hours ? `${duration.hours}hr` : ''}{' '}
                {duration.minutes ? `${duration.minutes}mins` : ''}
              </Text>
            </Div>
            <Spacer />
            <Div>
              <Text mb={3}>{event.status}</Text>
              <Div display={canTakeExam ? undefined : 'none'}>
                <ActionButton />
              </Div>
            </Div>
          </HStack>
        </Div>
        <Text fontSize={'2xl'} fontWeight={'bold'} my={5}>
          Subject(s)
        </Text>
        <VStack align={'stretch'} spacing={3} divider={<Divider />}>
          {event.event_courseables!.map((eventCourseable) => {
            return (
              <Div key={eventCourseable.id}>
                <Text>{eventCourseable.courseable?.course?.title}</Text>
              </Div>
            );
          })}
        </VStack>
      </CenteredBox>
    </ExamLayout>
  );
}

function CountDown({
  startDate,
  onTimeElapsed,
}: {
  startDate: Date;
  onTimeElapsed: () => void;
}) {
  const [remainingTime, setRemainingTime] = useState<string>('');

  useEffect(() => {
    const intervalId = setInterval(
      () => updateCountdown(() => clearInterval(intervalId)),
      1000
    );
    updateCountdown(() => clearInterval(intervalId));
    return () => clearInterval(intervalId);
  }, []);

  const updateCountdown = (timerElapsed: () => void) => {
    const currentTime = new Date().getTime();
    const timeRemaining = startDate.getTime() - currentTime;

    if (timeRemaining <= 0) {
      timerElapsed();
      setRemainingTime('0'); //Launch date has passed
      onTimeElapsed();
    } else {
      const days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
      const hours = Math.floor(
        (timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
      );
      const minutes = Math.floor(
        (timeRemaining % (1000 * 60 * 60)) / (1000 * 60)
      );
      const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);

      setRemainingTime(
        `${days} days ${hours} hrs ${minutes} mins ${seconds} secs`
      );
    }
  };

  return (
    <Div>
      <Text as={'h2'}>Countdown To Exam</Text>
      <Text fontSize={'3xl'} fontWeight={'bold'}>
        {remainingTime}
      </Text>
    </Div>
  );
}
