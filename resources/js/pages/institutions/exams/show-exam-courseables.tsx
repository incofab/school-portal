import React from 'react';
import {
  Event,
  ExamCourseable,
  Question,
  Student,
  TheoryQuestion,
} from '@/types/models';
import {
  HStack,
  Divider,
  VStack,
  Text,
  Icon,
  useColorModeValue,
  Badge,
  Alert,
  AlertIcon,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  SimpleGrid,
  Spacer,
  Tab,
  TabList,
  TabPanel,
  TabPanels,
  Tabs,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { ExamAttempt } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Div } from '@/components/semantic';
import { LabelText } from '@/components/result-helper-components';
import QuestionImageHandler from '@/util/exam/question-image-handler';
import { CheckIcon, XMarkIcon } from '@heroicons/react/24/outline';
import ExamUtil from '@/util/exam/exam-util';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import { preventNativeSubmit } from '@/util/util';
import { BrandButton } from '@/components/buttons';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  examCourseable: ExamCourseable;
}

export default function ShowExamCourseables({ examCourseable }: Props) {
  const exam = examCourseable.exam!;
  const courseable = examCourseable.courseable!;
  const student = exam.examable as Student;
  const questionImageHandler = new QuestionImageHandler(courseable);
  const borderColor = useColorModeValue('gray.300', 'gray.600');
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const theoryQuestions = examCourseable.courseable!.theory_questions ?? [];
  const webForm = useWebForm({
    scores: Object.fromEntries(
      theoryQuestions.map((question) => [
        question.id,
        examCourseable.theory_question_scores?.[question.id] ?? '',
      ])
    ),
  });

  async function submitTheoryScores() {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('exam-courseables.evaluate-theory', [
          exam.id,
          examCourseable.id,
        ]),
        data
      )
    );
    if (!handleResponseToast(res)) return;
    Inertia.reload();
  }

  const details = [
    { label: 'Event', value: exam.event?.title },
    { label: 'Exam No', value: exam.exam_no },
    { label: 'Subject', value: courseable.course?.title },
    { label: 'Objective Score', value: examCourseable.score },
    {
      label: 'Objective Questions',
      value: examCourseable.num_of_questions,
    },
    {
      label: 'Theory Score',
      value: `${examCourseable.theory_score} / ${examCourseable.theory_max_score}`,
    },
    {
      label: 'Theory Questions',
      value: examCourseable.theory_num_of_questions,
    },
    {
      label: 'Theory Status',
      value: examCourseable.theory_evaluated ? 'Evaluated' : 'Pending',
    },
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
          <Tabs colorScheme="brand" isLazy>
            <TabList overflowX={'auto'}>
              <Tab>Objective ({courseable.questions?.length ?? 0})</Tab>
              <Tab>Theory ({theoryQuestions.length})</Tab>
            </TabList>
            <TabPanels>
              <TabPanel px={0}>
                <VStack align={'stretch'} spacing={2}>
                  {(courseable.questions ?? []).map((question, index) => (
                    <Div
                      key={question.id}
                      border={'1px solid'}
                      borderColor={borderColor}
                      borderRadius={'5px'}
                      my={2}
                      p={3}
                    >
                      <ShowQuestion
                        event={exam.event!}
                        question={question}
                        attempts={exam!.attempts}
                        questionImageHandler={questionImageHandler}
                        index={index}
                      />
                    </Div>
                  ))}
                </VStack>
              </TabPanel>
              <TabPanel px={0}>
                <HStack mb={3}>
                  <Text fontWeight={'bold'}>Theory Evaluation</Text>
                  <Badge
                    colorScheme={
                      examCourseable.theory_evaluated ? 'green' : 'orange'
                    }
                  >
                    {examCourseable.theory_evaluated ? 'Evaluated' : 'Pending'}
                  </Badge>
                  <Spacer />
                  <Text fontSize={'sm'} color={'gray.500'}>
                    {examCourseable.theory_score} /{' '}
                    {examCourseable.theory_max_score} marks
                  </Text>
                </HStack>
                {theoryQuestions.length === 0 ? (
                  <Alert status="info" my={3}>
                    <AlertIcon />
                    This subject has no theory questions to evaluate.
                  </Alert>
                ) : (
                  <VStack
                    align={'stretch'}
                    spacing={3}
                    as={'form'}
                    onSubmit={preventNativeSubmit(submitTheoryScores)}
                  >
                    {theoryQuestions.map((question, index) => (
                      <Div
                        key={question.id}
                        border={'1px solid'}
                        borderColor={borderColor}
                        borderRadius={'5px'}
                        my={2}
                        p={3}
                      >
                        <ShowTheoryQuestion
                          question={question}
                          attempts={exam!.attempts}
                          questionImageHandler={questionImageHandler}
                          index={index}
                          score={webForm.data.scores[question.id] ?? ''}
                          error={
                            (webForm.errors as any)[`scores.${question.id}`]
                          }
                          onScoreChange={(score) =>
                            webForm.setValue('scores', {
                              ...webForm.data.scores,
                              [question.id]: score,
                            })
                          }
                        />
                      </Div>
                    ))}
                    <HStack justify={'end'}>
                      <BrandButton
                        type="submit"
                        isLoading={webForm.processing}
                        title={
                          examCourseable.theory_evaluated
                            ? 'Update Evaluation'
                            : 'Mark Theory Evaluated'
                        }
                      />
                    </HStack>
                  </VStack>
                )}
              </TabPanel>
            </TabPanels>
          </Tabs>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function ShowTheoryQuestion({
  question,
  attempts,
  questionImageHandler,
  index,
  score,
  error,
  onScoreChange,
}: {
  question: TheoryQuestion;
  attempts: ExamAttempt;
  questionImageHandler: QuestionImageHandler;
  index: number;
  score: string | number;
  error?: string;
  onScoreChange(score: string): void;
}) {
  const rawSelection = attempts[ExamUtil.getTheoryAttemptKey(question.id)];
  const selection =
    typeof rawSelection === 'object'
      ? rawSelection?.attempt ?? 'Not answered'
      : rawSelection ?? 'Not answered';
  return (
    <Div>
      <Text as={'span'} fontWeight={'bold'}>
        No {index + 1} ({question.marks} marks){' '}
      </Text>
      <Text
        as={'span'}
        dangerouslySetInnerHTML={{
          __html: questionImageHandler.handleImages(question.question),
        }}
      />
      <Div mt={3}>
        <Text fontWeight={'bold'}>Student Answer</Text>
        <Text whiteSpace={'pre-wrap'}>{selection}</Text>
      </Div>
      <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4} mt={3}>
        <Div>
          <Text fontWeight={'bold'}>Expected Answer</Text>
          <Text
            dangerouslySetInnerHTML={{
              __html: questionImageHandler.handleImages(question.answer),
            }}
          />
          {question.marking_scheme && (
            <Div mt={3}>
              <Text fontWeight={'bold'}>Marking Scheme</Text>
              <Text
                dangerouslySetInnerHTML={{
                  __html: questionImageHandler.handleImages(
                    question.marking_scheme
                  ),
                }}
              />
            </Div>
          )}
        </Div>
        <FormControl isInvalid={!!error}>
          <FormLabel>Score out of {question.marks}</FormLabel>
          <Input
            type="number"
            min={0}
            max={question.marks}
            step="0.01"
            value={score}
            onChange={(e) => onScoreChange(e.currentTarget.value)}
          />
          <FormErrorMessage>{error}</FormErrorMessage>
        </FormControl>
      </SimpleGrid>
    </Div>
  );
}

function ShowQuestion({
  question,
  attempts,
  questionImageHandler,
  event,
  index,
}: {
  event: Event;
  question: Question;
  attempts: ExamAttempt;
  questionImageHandler: QuestionImageHandler;
  index: number;
}) {
  const rawSelection = attempts[question.id] ?? null;
  const selection =
    typeof rawSelection === 'object' ? rawSelection?.attempt : rawSelection;
  const isCorrect = selection === question.answer;
  return (
    <Div>
      <Text as={'span'} fontWeight={'bold'}>
        No {index + 1} {/* question.question_no */}
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
