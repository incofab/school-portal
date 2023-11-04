import { Avatar, Divider, HStack, Icon, Text, VStack } from '@chakra-ui/react';
import React from 'react';
import { Exam, TokenUser } from '@/types/models';
import { Div } from '@/components/semantic';
import ExamLayout from '../exam-layout';
import CenteredBox from '@/components/centered-box';
import { avatarUrl } from '@/util/util';
import { AcademicCapIcon } from '@heroicons/react/24/solid';

interface LeaderBoard extends Exam {
  total_score: number;
  exam_count: number;
  examable: TokenUser;
}
interface Props {
  leaderBoardExams: LeaderBoard[];
  exams: Exam[];
  tokenUser: TokenUser;
}

export default function ShowLeaderBoard({
  leaderBoardExams,
  exams,
  tokenUser,
}: Props) {
  return (
    <ExamLayout title={`Leader Board`}>
      <CenteredBox>
        <Text
          fontWeight={'bold'}
          fontSize={'3xl'}
          color={'brand.600'}
          textAlign={'center'}
        >
          Leader Board
        </Text>
        <Divider my={2} />
        <VStack align={'stretch'} spacing={2} divider={<Divider />}>
          {leaderBoardExams.map((exam) => {
            return (
              <Div key={exam.id} py={2}>
                <HStack justify={'space-between'}>
                  <HStack>
                    <Avatar
                      src={avatarUrl(exam.examable.name)}
                      width={'40px'}
                      height={'40px'}
                      border={'2px solid #2a8864'}
                    />
                    <Text fontSize={'2xl'}>{exam.examable.name}</Text>
                  </HStack>
                  <HStack>
                    <Icon
                      as={AcademicCapIcon}
                      fontSize={'34px'}
                      color={'teal.700'}
                    />
                    <Text fontWeight={'bold'} fontSize={'2xl'}>
                      {Math.floor(exam.total_score / exam.exam_count)}
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
