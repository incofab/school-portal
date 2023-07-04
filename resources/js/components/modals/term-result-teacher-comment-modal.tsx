import React from 'react';
import { Button, HStack, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import InputForm from '../forms/input-form';
import { TermResult } from '@/types/models';

interface Props {
  termResult: TermResult;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function TermResultTeacherCommentModal({
  isOpen,
  onSuccess,
  onClose,
  termResult,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    comment: termResult.teacher_comment,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('term-results.teacher-comment', [termResult]), data)
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={"Teacher's Comemnt"}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox
            form={webForm as any}
            title="Comment"
            formKey="comment"
          >
            <Textarea
              onChange={(e) =>
                webForm.setValue('comment', e.currentTarget.value)
              }
            >
              {webForm.data.comment}
            </Textarea>
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
            Save
          </Button>
        </HStack>
      }
    />
  );
}
