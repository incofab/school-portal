import React from 'react';
import { Event, ExamCourseable, Question, Student } from '@/types/models';
import {
  HStack,
  Divider,
  VStack,
  Text,
  Icon,
  useColorModeValue,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { ExamAttempt } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Div } from '@/components/semantic';
import { LabelText } from '@/components/result-helper-components';
import QuestionImageHandler from '@/util/exam/question-image-handler';
import { CheckIcon, XMarkIcon } from '@heroicons/react/24/outline';

interface Props {
  examCourseable: ExamCourseable;
}

export default function ShowExamCourseables({ examCourseable }: Props) {
  const exam = examCourseable.exam!;
  const courseable = examCourseable.courseable!;
  const student = exam.examable as Student;
  const questionImageHandler = new QuestionImageHandler(courseable);
  const details = [
    { label: 'Event', value: exam.event?.title },
    { label: 'Exam No', value: exam.exam_no },
    { label: 'Subject', value: courseable.course?.title },
    { label: 'Score', value: examCourseable.score },
    { label: 'Num of Questions', value: examCourseable.num_of_questions },
    { label: 'Student', value: student?.user?.full_name },
    { label: 'Student Id', value: student?.code },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={`${courseable.course?.title} Result Details`} />
        <SlabBody>
          {details.map((item) => (
            <LabelText
              key={item.label}
              label={item.label}
              text={item.value}
              labelProps={{ width: '150px' }}
            />
          ))}
          <Divider my={3} />
          <VStack align={'stretch'} spacing={2}>
            {examCourseable.courseable!.questions!.map((question) => (
              <Div
                key={question.id}
                border={'1px solid'}
                borderColor={useColorModeValue('gray.300', 'gray.600')}
                borderRadius={'5px'}
                my={2}
                p={3}
              >
                <ShowQuestion
                  event={exam.event!}
                  question={question}
                  attempts={exam!.attempts}
                  questionImageHandler={questionImageHandler}
                />
                {/* <Divider my={3} height={2} background={'gray.400'} /> */}
              </Div>
            ))}
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function ShowQuestion({
  question,
  attempts,
  questionImageHandler,
  event,
}: {
  event: Event;
  question: Question;
  attempts: ExamAttempt;
  questionImageHandler: QuestionImageHandler;
}) {
  const selection = attempts[question.id] ?? null;
  const isCorrect = selection === question.answer;
  return (
    <Div>
      <Text as={'span'} fontWeight={'bold'}>
        No {question.question_no}
      </Text>
      <Text
        as={'span'}
        dangerouslySetInnerHTML={{
          __html: questionImageHandler.handleImages(question.question),
        }}
      />
      <Div>
        {[
          {
            optionText: questionImageHandler.handleImages(question.option_a),
            optionLetter: 'A',
          },
          {
            optionText: questionImageHandler.handleImages(question.option_b),
            optionLetter: 'B',
          },
          {
            optionText: questionImageHandler.handleImages(question.option_c),
            optionLetter: 'C',
          },
          {
            optionText: questionImageHandler.handleImages(question.option_d),
            optionLetter: 'D',
          },
          {
            optionText: questionImageHandler.handleImages(question.option_e),
            optionLetter: 'E',
          },
        ].map((item) => {
          const isSelection = item.optionLetter === selection;
          const isAnswer =
            event.show_corrections && item.optionLetter === question.answer;
          return item.optionText ? (
            <HStack
              key={item.optionLetter}
              align={'stretch'}
              background={isAnswer ? 'green.50' : 'transparent'}
              color={isAnswer ? 'green.900' : ''}
              py={3}
            >
              <Text>
                {item.optionLetter}){' '}
                {isSelection ? (
                  <Icon
                    as={
                      !event.show_corrections
                        ? CheckIcon
                        : isCorrect
                        ? CheckIcon
                        : XMarkIcon
                    }
                    color={
                      event.show_corrections && isCorrect
                        ? 'green.500'
                        : 'red.500'
                    }
                  />
                ) : null}
              </Text>
              <Text
                dangerouslySetInnerHTML={{
                  __html: questionImageHandler.handleImages(item.optionText),
                }}
              />
            </HStack>
          ) : (
            <span key={item.optionLetter}></span>
          );
        })}
      </Div>
    </Div>
  );
}
