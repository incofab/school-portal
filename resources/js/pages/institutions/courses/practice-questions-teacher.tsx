import React, { useState } from 'react';
import { Course, PracticeQuestion } from '@/types/models';
import {
  HStack,
  Text,
  VStack,
  Box,
  Checkbox,
  Button,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import InsertQuestionsIntoQuestionbankModal from '@/components/modals/insert-questions-into-questionbank-modal';

interface Props {
  practiceQuestions: PracticeQuestion[];
  course: Course;
}

export default function PracticeQuestionsTeacher({ practiceQuestions, course }: Props) {
  const [selectedQuestions, setSelectedQuestions] = useState<PracticeQuestion[]>([]);
  const insertQuestionsIntoQuestionbankModalToggle = useModalValueToggle<Course>();
  const [courseData, setCourseData] = useState<Course>(course);

  const handleCheckboxChange = (question: PracticeQuestion) => {
    setSelectedQuestions((prevSelected) => {
      if (prevSelected.some((q) => q.question === question.question)) {        
        return prevSelected.filter((q) => q.question !== question.question); // If the question is already selected, remove it
      } else {        
        return [...prevSelected, question]; // Otherwise, add the question to the selected list
      }
    });
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Select the questions you wish to save into Question Bank" />
        <SlabBody>
          {practiceQuestions.map((q) => (
            <Box key={q.question} mb={5}>
              <HStack spacing={4}>
                <Checkbox
                  isChecked={selectedQuestions.some((selected) => selected.question === q.question)}
                  onChange={() => handleCheckboxChange(q)}
                >
                  <Text fontSize="lg">{q.question}</Text>
                </Checkbox>
              </HStack>

              <VStack align="start" spacing={1}>
                <VStack align="stretch" ml={10}>
                  {['option_a', 'option_b', 'option_c', 'option_d'].map((optionKey) => {
                    return (
                      <Text key={optionKey}>
                        {q[optionKey]}
                      </Text>
                    );
                  })}
                </VStack>
              </VStack>
            </Box>
          ))}

          <Button 
            colorScheme={'brand'}
            onClick={() => insertQuestionsIntoQuestionbankModalToggle.open(courseData)}
            mt={4}
            isDisabled={selectedQuestions.length === 0} // Disable if no question is selected
          >
            Save Selected Questions
          </Button>
        </SlabBody>
      </Slab>

      {insertQuestionsIntoQuestionbankModalToggle.state && (
        <InsertQuestionsIntoQuestionbankModal
          {...insertQuestionsIntoQuestionbankModalToggle.props}
          course={courseData}
          questions={selectedQuestions}
          onSuccess={(newCourseData) => { newCourseData && setCourseData(newCourseData) }}
        />
      )}
    </DashboardLayout>
  );
}
