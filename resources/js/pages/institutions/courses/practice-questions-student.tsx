import React, { useState } from 'react';
import { PracticeQuestion } from '@/types/models';
import {
  HStack,
  Icon,
  Text,
  RadioGroup,
  VStack,
  Radio,
  Box,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton } from '@/components/buttons';
import { XCircleIcon, CheckCircleIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';

interface Props {
  practiceQuestions: PracticeQuestion[];
}

export default function PracticeQuestionsStudent({ practiceQuestions }: Props) {
  const [answers, setAnswers] = useState<{ [key: number]: string }>({});
  const [submitted, setSubmitted] = useState(false);

  const handleSelect = (index: number, value: string) => {
    setAnswers({ ...answers, [index]: value });
  };

  const handleSubmit = () => {
    setSubmitted(true);
  };

  // Calculate statistics
  const totalQuestions = practiceQuestions?.length ?? 0;
  const attemptedQuestions = Object.keys(answers).length; // Number of answered questions
  const correctAnswers =
    practiceQuestions?.filter((q, index) => answers[index] === 'option_'+q.answer)
      .length ?? 0;

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Practice Questions" />
        <SlabBody>
          {practiceQuestions.map((q, index) => (
            <Box key={index} mb={5}>
              <Text>
                <strong>Q{index + 1}:</strong> {q.question}
              </Text>

              <RadioGroup
                mt={2}
                onChange={(val) => handleSelect(index, val)}
                value={answers[index] || ''}
                isDisabled={submitted}
              >
                <VStack align="stretch" ml={10}>
                  {['option_a', 'option_b', 'option_c', 'option_d'].map((optionKey) => {
                    const isCorrect = 'option_'+q.answer === optionKey;
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
                border="2px solid green"
                borderRadius="8px"
                w={600}
              >
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
                  <strong>Score:</strong>{' '}
                  {Math.round((correctAnswers / totalQuestions) * 100)}%
                </Text>
              </Box>
            ) : (
              <BrandButton onClick={handleSubmit} mt={4}>
                Submit
              </BrandButton>
            )}{' '}
          </Div>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
