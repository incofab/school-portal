import React from 'react';
import { Button, HStack, Input, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { AssignmentSubmission } from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  assignmentSubmission: AssignmentSubmission;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function AssignmentScoreModal({
  assignmentSubmission,
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    score: '',
    remark: '',
    assignmentSubmission: assignmentSubmission,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('assignment-submission.score', [assignmentSubmission]),
        data
      )
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();

    Inertia.visit(instRoute('assignments.index'));
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Score this Submission'}
      bodyContent={
        <VStack spacing={2} align={'stretch'} style={{ marginTop: '20px' }}>
          <FormControlBox
            form={webForm as any}
            title="Enter Score"
            formKey="score"
            isRequired
          >
            <Input
              type="number"
              onChange={(e) => webForm.setValue('score', e.currentTarget.value)}
              value={webForm.data.score}
            />
          </FormControlBox>

          <FormControlBox
            form={webForm as any}
            title="Remark [optional]"
            formKey="remark"
          >
            <Textarea
              onChange={(e) =>
                webForm.setValue('remark', e.currentTarget.value)
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
