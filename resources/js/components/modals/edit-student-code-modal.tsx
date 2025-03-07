import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import { Student } from '@/types/models';
import InputForm from '../forms/input-form';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  student: Student;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function EditStudentCodeModal({
  isOpen,
  onSuccess,
  onClose,
  student,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    code: student.code,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('students.update-code', [student.id]), data)
    );

    if (!handleResponseToast(res)) {
      return;
    }

    webForm.reset();
    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Edit Student Code'}
      bodyContent={
        <VStack spacing={2}>
          <InputForm
            form={webForm as any}
            formKey="code"
            title="Student Code"
            isRequired
          />
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
