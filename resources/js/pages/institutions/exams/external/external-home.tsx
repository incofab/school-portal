import { Divider, HStack, Spacer, Text, VStack } from '@chakra-ui/react';
import React from 'react';
import { Event, Exam, TokenUser } from '@/types/models';
import { LinkButton } from '@/components/buttons';
import { Div } from '@/components/semantic';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ExamLayout from '../exam-layout';
import CenteredBox from '@/components/centered-box';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { intervalToDuration } from 'date-fns';
import DateTimeDisplay from '@/components/date-time-display';
import { InertiaLink } from '@inertiajs/inertia-react';

interface Props {
  events: Event[];
  exams: Exam[];
  tokenUser: TokenUser;
}

export default function ExternalHome({ events, exams, tokenUser }: Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <ExamLayout title={`Welcome ${tokenUser.name}`}>
      <CenteredBox>
        {events && events.length > 0 && (
          <Slab>
            <SlabHeading title="Active events" />
            <SlabBody>
              <VStack align={'stretch'} spacing={3} divider={<Divider />}>
                {events.map((ev) => {
                  const duration = intervalToDuration({
                    start: 0,
                    end: ev.duration * 60 * 1000,
                  });
                  return (
                    <Div key={ev.id}>
                      <HStack align={'stretch'} justify={'space-between'}>
                        <Text
                          as={InertiaLink}
                          href={instRoute('external.events.show', [ev])}
                          color={'brand.700'}
                        >
                          {ev.title}
                        </Text>
                        <Spacer />
                        <Text>{ev.status}</Text>
                      </HStack>
                      <HStack mt={2}>
                        <Div>
                          <Text as={'span'}>Starts at: </Text>
                          {/* <Text as={'span'}>{ev.starts_at}</Text> */}
                          <DateTimeDisplay
                            as={'span'}
                            dateTime={ev.starts_at}
                          />
                        </Div>
                        <Spacer />
                        <Div>
                          <Text as={'span'}>Duration: </Text>
                          <Text as={'span'}>
                            {duration.hours ? `${duration.hours}hr` : ''}{' '}
                            {duration.minutes ? `${duration.minutes}mins` : ''}
                          </Text>
                        </Div>
                        {/* 
                        <Spacer />
                        <Div>
                          {!ev.exams ||
                            (ev.exams.length == 0 && (
                              <LinkButton
                                href={instRoute('external.exams.create', [ev])}
                                title="Take Exam"
                              />
                            ))}
                        </Div> */}
                      </HStack>
                    </Div>
                  );
                })}
              </VStack>
            </SlabBody>
          </Slab>
        )}
        {exams && exams.length > 0 && (
          <>
            <Spacer height={'30px'} />
            <Slab>
              <SlabHeading title="My Exams" />
              <SlabBody>
                <VStack align={'stretch'} spacing={3} divider={<Divider />}>
                  {exams.map((exam) => {
                    const rem = intervalToDuration({
                      start: 0,
                      end: exam.time_remaining * 1000,
                    });
                    return (
                      <Div key={exam.id}>
                        <HStack align={'stretch'} justify={'space-between'}>
                          <Text>Exam No: {exam.exam_no}</Text>
                          <Spacer />
                          <Text>{exam.status}</Text>
                        </HStack>
                        <HStack
                          align={'stretch'}
                          justify={'space-between'}
                          key={exam.id}
                          mt={2}
                        >
                          <Div>
                            <Text as={'span'}>Rem: </Text>
                            <Text as={'span'}>
                              {rem.hours ? `${rem.hours}hr` : ''}{' '}
                              {rem.minutes ? `${rem.minutes}mins` : ''}
                              {rem.seconds ? `${rem.seconds}secs` : ''}
                            </Text>
                          </Div>
                          {(exam.status === 'active' ||
                            exam.status === 'pending') && (
                            <LinkButton
                              href={instRoute('display-exam-page', [
                                exam.exam_no,
                              ])}
                              title="Continue Exam"
                            />
                          )}
                        </HStack>
                      </Div>
                    );
                  })}
                </VStack>
              </SlabBody>
            </Slab>
          </>
        )}
      </CenteredBox>
    </ExamLayout>
  );
}
