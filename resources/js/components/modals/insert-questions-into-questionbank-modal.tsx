import React, { useState } from 'react';
import { Button, HStack, VStack, Icon, Text } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import { Course, PracticeQuestion} from '@/types/models';
import { SelectOptionType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Div } from '../semantic';
import { BrandButton } from '../buttons';
import { PlusIcon } from '@heroicons/react/24/solid';
import CourseSessionSelect from '../selectors/course-session-select';
import { SingleValue } from 'react-select';
import CreateSessionModal from './create-session-modal';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';

interface Props {
  course: Course;
  questions: PracticeQuestion[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(newCourseData?: Course): void;
}

export default function InsertQuestionsIntoQuestionbankModal({
  isOpen,
  onSuccess,
  onClose,
  course,
  questions,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const createSessionModalToggle = useModalValueToggle<Course>();
  const [courseSession, setCourseSession] = useState(
    {} as SingleValue<SelectOptionType<number>>
  );

  const webForm = useWebForm({
    questions: questions,
  });

  const onSubmit = async () => {

    const res = await webForm.submit((data, web) =>
      web.post(instRoute('courses.insert-questions', [courseSession?.value]), data)
    );

    if (!handleResponseToast(res)) {
      return;
    }
    
    onClose();
    // onSuccess(res.data.practice_questions);
    onSuccess();
  };

  const updateCourseData = (newCourseData: Course) => {
    onSuccess(newCourseData);
  }


  return (
    <>
      <GenericModal
        props={{ isOpen, onClose }}
        headerContent={`Session - `+course.title}
        bodyContent={
          <VStack spacing={1} align={'stretch'} mb={5}>
            <Text fontSize={'md'} fontWeight={'semibold'}>Select Session</Text>
            <HStack spacing={4} align={'stretch'} verticalAlign={'centered'}>
              <Div minW={'250px'}>
                <CourseSessionSelect
                  selectValue={courseSession?.value}
                  courseSessions={course.sessions ?? []}
                  onChange={(e: any) => setCourseSession(e)}
                />
              </Div>
              
              <BrandButton
                leftIcon={<Icon as={PlusIcon} />}
                title="New Session"
                mt={1}
                onClick={() => createSessionModalToggle.open(course)}
              />
            </HStack>
          </VStack>
        }

        footerContent={
          <HStack spacing={2}>
            <Button variant={'ghost'} onClick={onClose}>
              Close
            </Button>
            <Button
              colorScheme={'brand'}
              onClick={onSubmit}
              isLoading={webForm.processing}
            >
              Submit
            </Button>
          </HStack>
        }
      />

      {createSessionModalToggle.state && (
        <CreateSessionModal
          {...createSessionModalToggle.props}
          course={course}
          onSuccess={(newCourseData) => updateCourseData(newCourseData)}
        />
      )}
    </>
  );
}
