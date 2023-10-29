import React from 'react';
import { Exam, TokenUser } from '@/types/models';
import { Divider, HStack, Icon, Text, VStack } from '@chakra-ui/react';
import ExamLayout from '../exam-layout';
import { AcademicCapIcon } from '@heroicons/react/24/solid';
import { LabelText } from '@/components/result-helper-components';
import { Div } from '@/components/semantic';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import CenteredBox from '@/components/centered-box';

interface Props {
  exam: Exam;
  tokenUser: TokenUser;
}

export default function ExamResult({ exam, tokenUser }: Props) {
  const { instRoute } = useInstitutionRoute();
  const displayData = [
    { label: 'Exam No', value: exam.exam_no },
    { label: 'Num of Subjects', value: exam.exam_courseables?.length },
    { label: 'Num of Questions', value: exam.num_of_questions },
    { label: 'Total Score', value: exam.score },
  ];
  return (
    <ExamLayout
      title={exam.event?.title}
      rightElement={
        <Div>
          <Text fontWeight={'bold'} color={'brand.100'}>
            Congratulations
          </Text>
          <Text>{`${tokenUser.name}`}</Text>
        </Div>
      }
    >
      <CenteredBox>
        <VStack align={'stretch'} spacing={3}>
          <Text fontWeight={'semibold'} fontSize={'3xl'} textAlign={'center'}>
            {exam.event?.title}
          </Text>
          <HStack justify={'space-between'} px={3}>
            <Icon as={AcademicCapIcon} fontSize={'7xl'} color={'brand.700'} />
            <Div
              borderRadius={'50%'}
              p={3}
              background={'brand.500'}
              color={'white'}
            >
              <Text
                fontWeight={'bold'}
                fontSize={'3xl'}
              >{`${exam.score}/${exam.num_of_questions}`}</Text>
            </Div>
          </HStack>
          <br />
          <VStack align={'stretch'} spacing={3}>
            {displayData.map(({ label, value }) => (
              <LabelText
                key={label}
                label={label}
                text={value}
                labelProps={{ width: '150px' }}
              />
            ))}
          </VStack>
          <br />
          <VStack align={'stretch'} spacing={3} divider={<Divider />}>
            <HStack
              align={'stretch'}
              justify={'space-between'}
              fontWeight={'bold'}
            >
              <Text>Subject(s)</Text>
              <Text>Num of Questions</Text>
              <Text>Score</Text>
            </HStack>
            {exam.exam_courseables?.map((examCoursable) => {
              return (
                <HStack
                  align={'stretch'}
                  justify={'space-between'}
                  key={examCoursable.id}
                >
                  <Text>{examCoursable.courseable?.course?.title}</Text>
                  <Text textAlign={'center'}>
                    {examCoursable.num_of_questions}
                  </Text>
                  <Text textAlign={'center'}>{examCoursable.score}</Text>
                </HStack>
              );
            })}
          </VStack>
          <br />
          <LinkButton title="Home" href={instRoute('external.home')} />
        </VStack>
      </CenteredBox>
    </ExamLayout>
  );
}
