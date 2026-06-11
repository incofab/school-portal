import React, { useState } from 'react';
import {
  PracticeQuestion,
  Topic,
  TopicPracticeAttempt,
  TopicPracticeSummary,
} from '@/types/models';
import {
  HStack,
  Icon,
  Text,
  RadioGroup,
  VStack,
  Radio,
  Box,
  Badge,
  Divider,
  SimpleGrid,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton } from '@/components/buttons';
import { XCircleIcon, CheckCircleIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';

type PracticeOptionKey = 'option_a' | 'option_b' | 'option_c' | 'option_d';

interface Props {
  practiceQuestions: PracticeQuestion[];
  attemptId?: number;
  topic?: Topic;
  practiceSummary?: TopicPracticeSummary;
}

function normalizeAnswer(answer?: string) {
  return (answer ?? '').replace('option_', '').toLowerCase();
}

export default function PracticeQuestionsStudent({
  practiceQuestions,
  attemptId,
  topic,
  practiceSummary,
}: Props) {
  const [answers, setAnswers] = useState<{ [key: number]: string }>({});
  const [submitted, setSubmitted] = useState(false);
  const [attemptResult, setAttemptResult] = useState<TopicPracticeAttempt>();
  const [summary, setSummary] = useState<TopicPracticeSummary | undefined>(
    practiceSummary
  );
  const form = useWebForm({});
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();

  const handleSelect = (index: number, value: string) => {
    setAnswers({ ...answers, [index]: value });
  };

  const handleSubmit = async () => {
    if (!attemptId) {
      setSubmitted(true);
      return;
    }

    const res = await form.submit((_, web) =>
      web.post(instRoute('courses.practice-questions.submit'), {
        attempt_id: attemptId,
        answers,
      })
    );

    if (!handleResponseToast(res)) {
      return;
    }

    setAttemptResult(res.data.attempt);
    setSummary(res.data.summary);
    setSubmitted(true);
  };

  // Calculate statistics
  const totalQuestions = practiceQuestions?.length ?? 0;
  const attemptedQuestions = Object.keys(answers).length; // Number of answered questions
  const correctAnswers =
    attemptResult?.score ??
    practiceQuestions?.filter(
      (q, index) =>
        normalizeAnswer(answers[index]) === normalizeAnswer(q.answer)
    ).length ??
    0;
  const percentage =
    attemptResult?.percentage ??
    (totalQuestions > 0
      ? Math.round((correctAnswers / totalQuestions) * 100)
      : 0);

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={topic ? `Practice: ${topic.title}` : 'Practice Questions'}
        />
        <SlabBody>
          {summary && (
            <SimpleGrid columns={{ base: 1, md: 4 }} spacing={3} mb={5}>
              <Box borderWidth="1px" borderRadius="8px" p={3}>
                <Text color="gray.500" fontSize="sm">
                  Generated Attempts
                </Text>
                <Text fontSize="2xl" fontWeight="bold">
                  {summary.attempts_count}
                </Text>
              </Box>
              <Box borderWidth="1px" borderRadius="8px" p={3}>
                <Text color="gray.500" fontSize="sm">
                  Best Score
                </Text>
                <Text fontSize="2xl" fontWeight="bold">
                  {summary.best_score}/{summary.best_questions_count}
                </Text>
              </Box>
              <Box borderWidth="1px" borderRadius="8px" p={3}>
                <Text color="gray.500" fontSize="sm">
                  Best Percentage
                </Text>
                <Text fontSize="2xl" fontWeight="bold">
                  {summary.best_percentage}%
                </Text>
              </Box>
              <Box borderWidth="1px" borderRadius="8px" p={3}>
                <Text color="gray.500" fontSize="sm">
                  Questions Generated
                </Text>
                <Text fontSize="2xl" fontWeight="bold">
                  {summary.latest_questions_count || totalQuestions}
                </Text>
              </Box>
            </SimpleGrid>
          )}

          {practiceQuestions.map((q, index) => (
            <Box key={index} mb={5} borderWidth="1px" borderRadius="8px" p={4}>
              <HStack justify="space-between" align="start">
                <Text>
                  <strong>Q{index + 1}:</strong> {q.question}
                </Text>
                {submitted && (
                  <Badge
                    colorScheme={
                      normalizeAnswer(answers[index]) ===
                      normalizeAnswer(q.answer)
                        ? 'green'
                        : 'red'
                    }
                  >
                    {normalizeAnswer(answers[index]) ===
                    normalizeAnswer(q.answer)
                      ? 'Correct'
                      : 'Review'}
                  </Badge>
                )}
              </HStack>

              <RadioGroup
                mt={2}
                onChange={(val) => handleSelect(index, val)}
                value={answers[index] || ''}
                isDisabled={submitted}
              >
                <VStack align="stretch" ml={10}>
                  {(
                    [
                      'option_a',
                      'option_b',
                      'option_c',
                      'option_d',
                    ] as PracticeOptionKey[]
                  ).map((optionKey) => {
                    const isCorrect =
                      normalizeAnswer(q.answer) === normalizeAnswer(optionKey);
                    const isSelected = answers[index] === optionKey;

                    return (
                      <HStack key={optionKey}>
                        <Radio value={optionKey} isDisabled={submitted}>
                          {q[optionKey]}
                        </Radio>

                        {submitted &&
                          (isCorrect ? (
                            <Icon
                              as={CheckCircleIcon}
                              color="green"
                              boxSize={5}
                            />
                          ) : isSelected ? (
                            <Icon as={XCircleIcon} color="red" boxSize={5} />
                          ) : null)}
                      </HStack>
                    );
                  })}
                </VStack>
              </RadioGroup>
            </Box>
          ))}

          {/* Display statistics after submission */}
          <Div alignContent={'center'} justifyContent={'center'}>
            {submitted ? (
              <Box
                mt={8}
                p={4}
                border="1px solid"
                borderColor="green.300"
                borderRadius="8px"
                w={{ base: '100%', md: 600 }}
              >
                <Text fontSize="lg" fontWeight="bold" mb={2}>
                  Practice Result
                </Text>
                <Divider mb={3} />
                <Text>
                  <strong>Total Questions:</strong> {totalQuestions}
                </Text>
                <Text>
                  <strong>Attempted Questions:</strong> {attemptedQuestions}
                </Text>
                <Text>
                  <strong>Correctly Answered Questions:</strong>{' '}
                  {correctAnswers}
                </Text>
                <Text>
                  <strong>Score:</strong> {correctAnswers}/{totalQuestions} (
                  {percentage}%)
                </Text>
              </Box>
            ) : (
              <BrandButton
                onClick={handleSubmit}
                mt={4}
                isLoading={form.processing}
                isDisabled={totalQuestions === 0}
              >
                Submit
              </BrandButton>
            )}{' '}
          </Div>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
