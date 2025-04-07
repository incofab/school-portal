import {
  Badge,
  Button,
  Divider,
  HStack,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
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
import { ExamStatus } from '@/types/types';

interface EventWithProps extends Event {
  exams?: Exam[];
}
interface Props {
  events: EventWithProps[];
  exams: Exam[];
  tokenUser: TokenUser;
}

export default function ExternalHome({ events, exams, tokenUser }: Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <ExamLayout title={`Welcome ${tokenUser.name}`} examable={tokenUser}>
      <CenteredBox>
        {events && events.length > 0 && (
          <Slab>
            <SlabHeading title="Active events" />
            <SlabBody>
              <VStack align={'stretch'} spacing={4} divider={<Divider />}>
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
                      <HStack mt={3}>
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
                            {duration.hours ? `${duration.hours}hr(s)` : ''}{' '}
                            {duration.minutes ? `${duration.minutes}mins` : ''}
                          </Text>
                        </Div>
                      </HStack>
                      <Div
                        display={
                          Number(ev.exams?.length) > 0 ? undefined : 'none'
                        }
                        mt={3}
                        p={2}
                        backgroundColor={'green.50'}
                        rounded={'md'}
                      >
                        {ev.exams?.map((exam) => {
                          const rem = intervalToDuration({
                            start: 0,
                            end: exam.time_remaining * 1000,
                          });
                          return (
                            <Div key={exam.id}>
                              <HStack
                                align={'stretch'}
                                justify={'space-between'}
                              >
                                <Text>Exam No: {exam.exam_no}</Text>
                                <Spacer />
                                <Badge
                                  color={'black'}
                                  backgroundColor={'transparent'}
                                >
                                  {exam.status}
                                </Badge>
                              </HStack>
                              <HStack
                                align={'stretch'}
                                justify={'space-between'}
                                key={exam.id}
                                mt={2}
                              >
                                {exam.status === ExamStatus.Ended ? (
                                  <>
                                    <Text>
                                      Score:{' '}
                                      <Text
                                        as={'span'}
                                      >{`${exam.score}/${exam.num_of_questions}`}</Text>
                                    </Text>
                                    <Button
                                      variant={'link'}
                                      colorScheme="brand"
                                      as={InertiaLink}
                                      href={instRoute('external.exam-result', [
                                        exam.exam_no,
                                      ])}
                                    >
                                      View Result
                                    </Button>
                                  </>
                                ) : (
                                  <>
                                    <Div>
                                      <Text as={'span'}>Rem: </Text>
                                      <Text as={'span'}>
                                        {rem.hours ? `${rem.hours}hr` : ''}{' '}
                                        {rem.minutes
                                          ? `${rem.minutes}mins`
                                          : ''}
                                        {rem.seconds
                                          ? `${rem.seconds}secs`
                                          : ''}
                                      </Text>
                                    </Div>
                                    {(exam.status === ExamStatus.Active ||
                                      exam.status === ExamStatus.Pending) && (
                                      <LinkButton
                                        href={instRoute('display-exam-page', [
                                          exam.exam_no,
                                        ])}
                                        title="Continue Exam"
                                      />
                                    )}
                                  </>
                                )}
                              </HStack>
                            </Div>
                          );
                        })}
                      </Div>
                    </Div>
                  );
                })}
              </VStack>
            </SlabBody>
          </Slab>
        )}
      </CenteredBox>
    </ExamLayout>
  );
}
