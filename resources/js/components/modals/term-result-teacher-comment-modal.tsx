import React from 'react';
import { Button, HStack, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import { ResultCommentTemplate, TermResult } from '@/types/models';
import ResultUtil from '@/util/result-util';

interface Props {
  termResult: TermResult;
  resultCommentTemplate?: ResultCommentTemplate[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function TermResultTeacherCommentModal({
  isOpen,
  onSuccess,
  onClose,
  termResult,
  resultCommentTemplate,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const titles = ResultUtil.getClassificationGroupTitles(
    termResult.classification
  );
  const webForm = useWebForm({
    comment: ResultUtil.getTeachersComment(termResult, resultCommentTemplate),
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
      headerContent={`${titles.headOfClassPossessive} Comment`}
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
