import React, { useState } from 'react';
import { Exam, ExamCourseable, Question } from '@/types/models';
import { Div } from '@/components/semantic';
import {
  Center,
  HStack,
  Radio,
  Spacer,
  Tab,
  TabList,
  TabPanel,
  TabPanels,
  Tabs,
  Text,
  VStack,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import ExamUtil from '@/util/exam/exam-util';

interface Props {
  exam: Exam;
}

export default function DisplayExam({ exam }: Props) {
  const [key, setKey] = useState<string>('0');
  function updateExamUtil() {
    setKey(Math.random() + '');
  }
  const [examUtil, setExamUtil] = useState<ExamUtil>(
    new ExamUtil(updateExamUtil)
  );

  return (
    <Div>
      <Tabs key={key} index={examUtil.getTabManager().getCurrentTabIndex()}>
        <TabList>
          {exam.exam_courseables?.map((item, index) => (
            <Tab
              key={item.id}
              onClick={() => examUtil.getTabManager().setCurrentTabIndex(index)}
            >
              {item.courseable?.course?.title} {item.courseable?.session}
            </Tab>
          ))}
        </TabList>
        <TabPanels>
          {exam.exam_courseables?.map((examCourseable, index) => {
            examUtil.getTabManager().setTab(index, {
              currentQuestionIndex: 0,
              exam_courseable_id: examCourseable.id,
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
    </Div>
  );
}

function DisplayQuestion({
  examCourseable,
  examUtil,
}: {
  examCourseable: ExamCourseable;
  examUtil: ExamUtil;
}) {
  const questions = examCourseable.courseable!.questions!;
  const question = questions[0];
  return (
    <VStack align={'stretch'}>
      <Text fontWeight={'bold'}>
        Question {question.question_no} of {questions.length}
      </Text>
      <Text my={2} dangerouslySetInnerHTML={{ __html: question.question }} />
      <VStack align={'stretch'} spacing={1}>
        {[
          { optionText: question.option_a, optionLetter: 'A' },
          { optionText: question.option_b, optionLetter: 'B' },
          { optionText: question.option_c, optionLetter: 'C' },
          { optionText: question.option_d, optionLetter: 'D' },
          { optionText: question.option_e, optionLetter: 'E' },
        ].map((item) => (
          <DisplayOption
            key={item.optionLetter}
            optionText={item.optionText}
            optionLetter={item.optionLetter}
            examUtil={examUtil}
            question={question}
          />
        ))}
      </VStack>
      <Spacer />
      <Wrap>
        {questions.map((item, index) => (
          <WrapItem key={item.id}>
            <Center
              w="40px"
              h="35px"
              cursor={'pointer'}
              border={'solid 1px'}
              borderColor={'gray.500'}
              rounded={'md'}
            >
              {index + 1}
            </Center>
          </WrapItem>
        ))}
      </Wrap>
    </VStack>
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
        onChange={(e) => {
          examUtil.getAttemptManager().setAttempt(question.id, optionLetter);
        }}
      >
        <Text dangerouslySetInnerHTML={{ __html: optionText }} />
      </Radio>
    </HStack>
  );
}
