import {
  Avatar,
  Button,
  Divider,
  HStack,
  Icon,
  Text,
  VStack,
} from '@chakra-ui/react';
import React, { useState } from 'react';
import { Event, Exam, TokenUser } from '@/types/models';
import { Div } from '@/components/semantic';
import ExamLayout from '../exam-layout';
import CenteredBox from '@/components/centered-box';
import { avatarUrl } from '@/util/util';
import { AcademicCapIcon } from '@heroicons/react/24/solid';
import { PaginationResponse } from '@/types/types';
import MySelect from '@/components/dropdown-select/my-select';
import { InertiaLink } from '@inertiajs/inertia-react';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface LeaderBoard extends Exam {
  // total_score: number;
  // exam_count: number;
  examable: TokenUser;
}

interface Props {
  leaderBoardExams: PaginationResponse<LeaderBoard>;
  event: Event;
  events: Event[];
}

export default function ShowLeaderBoard({
  leaderBoardExams,
  event,
  events,
}: Props) {
  const [eventId, setEventId] = useState(event.id);
  const { instRoute } = useInstitutionRoute();
  return (
    <ExamLayout
      title={`Leader Board`}
      breadCrumbItems={[{ title: 'Leader Board' }]}
    >
      <CenteredBox>
        <HStack align={'stretch'}>
          <Div width={'full'}>
            <MySelect
              isMulti={false}
              selectValue={eventId}
              getOptions={() =>
                events.map((event) => {
                  return {
                    label: event.title,
                    value: event.id,
                  };
                })
              }
              onChange={(e: any) => setEventId(e.value)}
            />
          </Div>
          <Button
            as={InertiaLink}
            colorScheme="brand"
            variant={'solid'}
            size={'md'}
            href={instRoute('external.leader-board', [eventId])}
          >
            Go
          </Button>
        </HStack>
        <VStack align={'stretch'} spacing={2} divider={<Divider />} mt={2}>
          {leaderBoardExams.data.map((exam) => {
            return (
              <Div key={exam.id} py={2}>
                <HStack justify={'space-between'}>
                  <HStack>
                    <Avatar
                      src={avatarUrl(exam.examable?.name)}
                      width={'40px'}
                      height={'40px'}
                      border={'2px solid #2a8864'}
                    />
                    <Text fontSize={'2xl'}>{exam.examable?.name}</Text>
                  </HStack>
                  <HStack>
                    <Icon
                      as={AcademicCapIcon}
                      fontSize={'34px'}
                      color={'teal.700'}
                    />
                    <Text fontWeight={'bold'} fontSize={'2xl'}>
                      {Math.floor(exam.score)}
                    </Text>
                  </HStack>
                </HStack>
              </Div>
            );
          })}
        </VStack>
      </CenteredBox>
    </ExamLayout>
  );
}
