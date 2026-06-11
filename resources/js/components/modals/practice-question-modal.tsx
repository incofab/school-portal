import React from 'react';
import { Button, HStack, VStack, Input } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import { Course, PracticeQuestion, Topic, User } from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import route from '@/util/route';
import EnumSelect from '../dropdown-select/enum-select';
import { Gender, Nullable, SelectOptionType } from '@/types/types';
import TopicSelect from '../selectors/topic-select';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  course: Course;
  isOpen: boolean;
  onClose(): void;
  // onSuccess(practiceQuestions?: string): void;
  onSuccess(practiceQuestions?: PracticeQuestion[]): void;
}

export default function PracticeQuestionModal({
  isOpen,
  onSuccess,
  onClose,
  course,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    course: course,
    topic_id: null as Nullable<SelectOptionType<number>>,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('courses.practice-questions'), {
        ...data,
        topic_ids: data.topic_id ? [data.topic_id.value] : [],
      })
    );

    if (!handleResponseToast(res)) {
      return;
    }
    
    onClose();
    // onSuccess(res.data.practice_questions);
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={`Course Practice Questions`}
      bodyContent={
        <VStack spacing={3}>
          <FormControlBox
            form={webForm as any}
            title="Select Topic"
            formKey="topic_id"
          >
            <TopicSelect
              topics={course.topics}
              onChange={(e: any) => webForm.setValue('topic_id', e)}
              selectValue={webForm.data.topic_id}
              isMulti={false}
              isClearable={true}
            />
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
