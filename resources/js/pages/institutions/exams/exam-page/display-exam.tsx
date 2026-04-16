import React, { useEffect, useMemo, useState } from 'react';
import { Exam, ExamCourseable, Question, TheoryQuestion } from '@/types/models';
import {
  Button,
  Center,
  HStack,
  Icon,
  IconButton,
  Radio,
  Spacer,
  Tab,
  TabList,
  TabPanel,
  TabPanels,
  Tabs,
  Text,
  Textarea,
  VStack,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import ExamUtil from '@/util/exam/exam-util';
import ExamLayout from '../exam-layout';
import useModalToggle from '@/hooks/use-modal-toggle';
import CalculatorModal from '@/components/modals/calculator-modal';
import { CalculatorIcon } from '@heroicons/react/24/solid';
import { BrandButton } from '@/components/buttons';
import ExamTimer from '@/util/exam/exam-timer';
import { formatTime } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { ExamAttempt } from '@/types/types';
import QuestionImageHandler from '@/util/exam/question-image-handler';
import '@/style/exam-display.css';
import tokenUserUtil from '@/util/token-user-util';
import { useExamGuard } from '@/hooks/useExamGuard';

interface Props {
  exam: Exam;
  timeRemaining: number;
  existingAttempts: ExamAttempt;
}

export default function DisplayExam({
  exam,
  // tokenUser,
  timeRemaining,
  existingAttempts,
}: Props) {
  const [isExamActive, setIsExamActive] = useState(true);
  const [, forceRender] = useState(0);
  const [submitLoading, setSubmitLoading] = useState<boolean>(false);
  const webForm = useWebForm({});
  const calculatorModalToggle = useModalToggle();
  const { instRoute } = useInstitutionRoute();
  function updateExamUtil() {
    forceRender((value) => value + 1);
  }

  const examUtil = useMemo(() => {
    const examUtil = new ExamUtil(exam, existingAttempts, updateExamUtil);
    return examUtil;
  }, []);

  useExamGuard({
    enabled: isExamActive,
    onWarning: () =>
      window.alert(
        'Do not leave your screen when the exam is ongong. Your paper will be automatically submitted on repeated attempts'
      ),
    onTerminate: () => submitExamNow(),
  });

  async function onTimeElapsed() {
    await examUtil.getAttemptManager().sendAttempts(webForm);
    Inertia.visit(instRoute('external.exam-result', [exam.exam_no]));
  }

  function onIntervalPing() {
    examUtil.getAttemptManager().sendAttempts(webForm);
  }

  async function submitExam() {
    if (!confirm('Do you want to submit your exam?')) {
      return;
    }
    submitExamNow();
  }

  async function submitExamNow() {
    setIsExamActive(false);
    setSubmitLoading(true);
    await examUtil.getAttemptManager().sendAttempts(webForm);

    await webForm.submit((data, web) => {
      return web.post(instRoute('end-exam', [exam.id]));
    });
    setSubmitLoading(false);
    Inertia.visit(instRoute('external.exam-result', [exam.exam_no]));
  }

  function previousClicked() {
    examUtil
      .getTabManager()
      .setCurrentQuestion(examUtil.getExamNavManager().getGoPreviousIndex());
  }

  function nextClicked() {
    examUtil
      .getTabManager()
      .setCurrentQuestion(examUtil.getExamNavManager().getGoNextIndex());
  }

  const handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
    const target = event.target as HTMLElement;
    if (
      ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) ||
      target.isContentEditable
    ) {
      return;
    }
    const pressedKey = event.key.toUpperCase() ?? '';
    switch (pressedKey) {
      case 'A':
      case 'B':
      case 'C':
      case 'D': {
        if (examUtil.getTabManager().getCurrentQuestionType() !== 'objective') {
          return;
        }
        const currentQuestion = examUtil.getTabManager().getCurrentQuestion();
        if (!currentQuestion) {
          return;
        }
        examUtil.getAttemptManager().setAttempt(currentQuestion.id, pressedKey);
        break;
      }
      case 'N':
        nextClicked();
        break;
      case 'P':
        previousClicked();
        break;
      case 'S':
        submitExam();
        break;
      case 'R':
        break;
    }
  };

  return (
    <ExamLayout
      title={''}
      examable={exam.examable}
      breadCrumbItems={[
        { title: `${tokenUserUtil(exam.examable).getName()}`, href: '#' },
      ]}
      rightElement={
        <HStack>
          <IconButton
            icon={<Icon as={CalculatorIcon} />}
            aria-label="Calculator"
            onClick={calculatorModalToggle.open}
            variant={'ghost'}
            fontSize={'2xl'}
          />
          <TimerView
            timeRemaining={timeRemaining}
            onTimeElapsed={onTimeElapsed}
            onIntervalPing={onIntervalPing}
          />
        </HStack>
      }
      onKeyDown={handleKeyDown}
    >
      <Tabs index={examUtil.getTabManager().getCurrentTabIndex()}>
        <TabList overflowX={'auto'}>
          {exam.exam_courseables?.map((item, index) => (
            <Tab
              key={item.id}
              onClick={() => examUtil.getTabManager().setCurrentTabIndex(index)}
            >
              {item.courseable?.course?.title}
              {/* {item.courseable?.course?.title} {item.courseable?.session} */}
            </Tab>
          ))}
        </TabList>
        <TabPanels>
          {exam.exam_courseables?.map((examCourseable, index) => {
            const tab = examUtil.getTabManager().getTab(index);
            examUtil.getTabManager().setTab(index, {
              currentQuestionIndex: tab?.currentQuestionIndex ?? 0,
              exam_courseable_id: examCourseable.id,
              currentQuestionType: tab?.currentQuestionType ?? 'objective',
            });
            return (
              <TabPanel key={examCourseable.id}>
                <DisplayQuestion
                  examCourseable={examCourseable}
                  examUtil={examUtil}
                />
              </TabPanel>
            );
          })}
        </TabPanels>
      </Tabs>
      <CalculatorModal {...calculatorModalToggle.props} />
      <HStack
        justifyContent={'space-between'}
        px={3}
        py={2}
        mt={2}
        position={'absolute'}
        bottom={0}
        left={0}
        right={0}
      >
        <BrandButton
          title="Previous"
          onClick={previousClicked}
          width={'80px'}
        />
        <BrandButton
          title="Submit"
          onClick={submitExam}
          width={'80px'}
          isLoading={submitLoading}
        />
        <BrandButton title="Next" onClick={nextClicked} width={'80px'} />
      </HStack>
    </ExamLayout>
  );
}

function TimerView({
  timeRemaining,
  onTimeElapsed,
  onIntervalPing,
}: {
  timeRemaining: number;
  onTimeElapsed: () => void;
  onIntervalPing: () => void;
}) {
  const [timer, setTimer] = useState<string>('');

  useEffect(() => {
    const examTimer = new ExamTimer(onTimerTick, onTimeElapsed, onIntervalPing);
    examTimer.start(timeRemaining);
    return () => examTimer.stop();
  }, []);

  function onTimerTick(timeRemaining: number) {
    setTimer(formatTime(timeRemaining) + '');
  }
  return <Text>{timer}</Text>;
}

function DisplayQuestion({
  examCourseable,
  examUtil,
}: {
  examCourseable: ExamCourseable;
  examUtil: ExamUtil;
}) {
  const attemptManager = examUtil.getAttemptManager();
  const objectiveQuestions = examCourseable.courseable!.questions ?? [];
  const theoryQuestions = examCourseable.courseable!.theory_questions ?? [];
  const questionType = examUtil.getTabManager().getCurrentQuestionType();
  const questions = examUtil.getTabManager().getCurrentCourseableQuestions();
  const questionIndex = examUtil.getTabManager().getCurrentQuestionIndex();
  const question = questions[questionIndex];
  const courseable = examCourseable.courseable!;
  const questionImageHandler = new QuestionImageHandler(courseable);
  const questionNumber =
    questionType === 'objective'
      ? (question as Question | undefined)?.question_no
      : (question as TheoryQuestion | undefined)?.question_no;
  const currentAttemptKey =
    questionType === 'theory' && question
      ? ExamUtil.getTheoryAttemptKey(question.id)
      : question?.id;

  function setQuestionType(type: 'objective' | 'theory') {
    examUtil.getTabManager().setCurrentQuestionType(type);
  }

  if (!question) {
    return (
      <VStack align={'stretch'} className="question-container">
        <QuestionTypeSwitcher
          questionType={questionType}
          objectiveCount={objectiveQuestions.length}
          theoryCount={theoryQuestions.length}
          setQuestionType={setQuestionType}
        />
        <Text>No {questionType} questions for this subject.</Text>
      </VStack>
    );
  }
  const passage =
    questionType === 'objective'
      ? courseable.passages?.find(
          (item) => questionNumber! >= item.from && questionNumber! <= item.to
        )
      : null;
  const instruction =
    questionType === 'objective'
      ? courseable.instructions?.find(
          (item) => questionNumber! >= item.from && questionNumber! <= item.to
        )
      : null;

  return (
    <VStack align={'stretch'} className="question-container">
      <QuestionTypeSwitcher
        questionType={questionType}
        objectiveCount={objectiveQuestions.length}
        theoryCount={theoryQuestions.length}
        setQuestionType={setQuestionType}
      />
      <Text fontWeight={'bold'}>
        Question {questionIndex + 1} of {questions.length}
        {questionType === 'theory' && (
          <Text as={'span'} fontWeight={'normal'}>
            {' '}
            ({(question as TheoryQuestion).marks} marks)
          </Text>
        )}
      </Text>
      {passage && (
        <Text my={2} dangerouslySetInnerHTML={{ __html: passage.passage }} />
      )}
      {instruction && (
        <Text
          my={2}
          dangerouslySetInnerHTML={{ __html: instruction.instruction }}
        />
      )}
      <Text
        my={2}
        dangerouslySetInnerHTML={{
          __html: questionImageHandler.handleImages(question.question),
        }}
      />
      {questionType === 'objective' ? (
        <VStack align={'stretch'} spacing={1}>
          {[
            {
              optionText: questionImageHandler.handleImages(
                (question as Question).option_a
              ),
              optionLetter: 'A',
            },
            {
              optionText: questionImageHandler.handleImages(
                (question as Question).option_b
              ),
              optionLetter: 'B',
            },
            {
              optionText: questionImageHandler.handleImages(
                (question as Question).option_c
              ),
              optionLetter: 'C',
            },
            {
              optionText: questionImageHandler.handleImages(
                (question as Question).option_d
              ),
              optionLetter: 'D',
            },
            {
              optionText: questionImageHandler.handleImages(
                (question as Question).option_e
              ),
              optionLetter: 'E',
            },
          ].map((item) => (
            <DisplayOption
              key={item.optionLetter}
              optionText={item.optionText}
              optionLetter={item.optionLetter}
              examUtil={examUtil}
              question={question as Question}
            />
          ))}
        </VStack>
      ) : (
        <Textarea
          minH={'180px'}
          value={attemptManager.getAttempt(currentAttemptKey!) ?? ''}
          onChange={(e) => {
            attemptManager.setAttempt(currentAttemptKey!, e.target.value);
          }}
          placeholder="Type your answer here"
        />
      )}
      <Spacer />
      <Wrap>
        {questions.map((item, index) => (
          <WrapItem key={item.id}>
            <Center
              w="40px"
              h="35px"
              cursor={'pointer'}
              border={'solid 1px'}
              borderColor={'gray.600'}
              rounded={'md'}
              onClick={() => {
                examUtil.getTabManager().setCurrentQuestion(index);
              }}
              backgroundColor={
                item.id === question.id
                  ? 'brand.500'
                  : attemptManager.isAttempted(
                      questionType === 'theory'
                        ? ExamUtil.getTheoryAttemptKey(item.id)
                        : item.id
                    )
                  ? 'brand.100'
                  : 'transparent'
              }
            >
              {index + 1}
            </Center>
          </WrapItem>
        ))}
      </Wrap>
    </VStack>
  );
}

function QuestionTypeSwitcher({
  questionType,
  objectiveCount,
  theoryCount,
  setQuestionType,
}: {
  questionType: 'objective' | 'theory';
  objectiveCount: number;
  theoryCount: number;
  setQuestionType: (type: 'objective' | 'theory') => void;
}) {
  return (
    <HStack>
      <Button
        size={'sm'}
        variant={questionType === 'objective' ? 'solid' : 'outline'}
        onClick={() => setQuestionType('objective')}
      >
        Objective ({objectiveCount})
      </Button>
      <Button
        size={'sm'}
        variant={questionType === 'theory' ? 'solid' : 'outline'}
        onClick={() => setQuestionType('theory')}
      >
        Theory ({theoryCount})
      </Button>
    </HStack>
  );
}

function DisplayOption({
  optionText,
  optionLetter,
  examUtil,
  question,
}: {
  optionText: string;
  optionLetter: string;
  question: Question;
  examUtil: ExamUtil;
}) {
  if (!optionText) {
    return null;
  }
  return (
    <HStack align={'stretch'}>
      <Text fontWeight={'md'}>{optionLetter + ')'}</Text>
      <Radio
        isChecked={
          examUtil.getAttemptManager().getAttempt(question.id) === optionLetter
        }
        onChange={() => {
          examUtil.getAttemptManager().setAttempt(question.id, optionLetter);
        }}
      >
        <Text dangerouslySetInnerHTML={{ __html: optionText }} />
      </Radio>
    </HStack>
  );
}
