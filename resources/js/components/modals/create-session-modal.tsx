import React from 'react';
import { Button, HStack, VStack, Input, Textarea } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import { Course, CourseSession, PracticeQuestion, Topic, User } from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import route from '@/util/route';
import EnumSelect from '../dropdown-select/enum-select';
import { Gender, Nullable, SelectOptionType } from '@/types/types';
import TopicSelect from '../selectors/topic-select';
import { MultiValue } from 'react-select';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';
import InputForm from '../forms/input-form';

interface Props {
  course: Course;
  isOpen: boolean;
  onClose(): void;
  // onSuccess(): void;
  onSuccess(newCourse: Course): void;
}

export default function CreateSessionModal({
  isOpen,
  onSuccess,
  onClose,
  course,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    session: '',
    category: '',
    general_instructions: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('course-sessions.store', [course]), data)
    );

    if (!handleResponseToast(res)) {
      return;
    }
    
    onClose();
    onSuccess(res.data.new_course_data);
    // onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={`Create Session - `+course.title}
      bodyContent={
        <VStack spacing={3} align={'stretch'}>
          <InputForm form={webForm as any} formKey="session" title="Session" isRequired/>
          <InputForm form={webForm as any} formKey="category" title="category" />         
          
          <FormControlBox
            form={webForm as any}
            title="General Instructions [optional]"
            formKey="general_instructions"
          >
            <Textarea
              onChange={(e) =>
                webForm.setValue('general_instructions', e.currentTarget.value)
              }
            ></Textarea>
          </FormControlBox>
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
  );
}
