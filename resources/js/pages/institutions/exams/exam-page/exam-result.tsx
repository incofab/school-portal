import React from 'react';
import { Exam, TokenUser } from '@/types/models';
import { HStack, Icon, Text, VStack } from '@chakra-ui/react';
import ExamLayout from '../exam-layout';
import { AcademicCapIcon } from '@heroicons/react/24/solid';
import { LabelText } from '@/components/result-helper-components';
import { Div } from '@/components/semantic';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  exam: Exam;
  tokenUser: TokenUser;
}

export default function ExamResult({ exam, tokenUser }: Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <ExamLayout
      title={`Congratulations, ${tokenUser.name}`}
      rightElement={
        <HStack>
          <Text>{`${exam.score}/${exam.num_of_questions}`}</Text>
        </HStack>
      }
    >
      <VStack align={'stretch'} spacing={3}>
        <Text fontWeight={'semibold'} fontSize={'lg'}>
          {exam.event?.title}
        </Text>
        <HStack>
          <Icon as={AcademicCapIcon} fontSize={'3xl'} mx={'auto'} />
          <Div
            borderRadius={'50%'}
            p={3}
            background={'brand.500'}
            color={'white'}
          >
            <Text
              fontWeight={'bold'}
            >{`${exam.score}/${exam.num_of_questions}`}</Text>
          </Div>
        </HStack>
        <VStack align={'stretch'}>
          {exam.exam_courseables?.map((examCoursable) => {
            return (
              <LabelText
                label="Subject"
                text={`${examCoursable.courseable?.course?.title} - ${examCoursable.courseable?.session}`}
              />
            );
          })}
        </VStack>
        <LinkButton title="Home" href={instRoute('external.home')} />
      </VStack>
    </ExamLayout>
  );
}
