import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { preventNativeSubmit } from '@/util/util';
import useSharedProps from '@/hooks/use-shared-props';
import { Inertia } from '@inertiajs/inertia';
import FormControlBox from '@/components/forms/form-control-box';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import ClassificationSelect from '@/components/selectors/classification-select';
import { AcademicSession, Classification } from '@/types/models';

interface Props {
  isOpen: boolean;
  onClose(): void;
  classifications: Classification[];
  academicSessions: AcademicSession[];
}

export default function SelectCourseSessionResultModal({
  isOpen,
  onClose,
  classifications,
  academicSessions,
}: Props) {
  const { toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm, usesMidTermResult } =
    useSharedProps();
  const webForm = useWebForm({
    academicSession: currentAcademicSessionId,
    classification: '',
  });

  const onSubmit = async () => {
    if (!webForm.data.academicSession || !webForm.data.classification) {
      toastError('Select a class and academic session');
      return;
    }
    Inertia.visit(
      instRoute('course-session-results', [
        webForm.data.academicSession,
        webForm.data.classification,
      ])
    );
    onClose();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Session Result Summary'}
      bodyContent={
        <VStack>
          <FormControlBox
            form={webForm as any}
            title="Academic Session"
            formKey="academicSession"
            isRequired
          >
            <AcademicSessionSelect
              selectValue={webForm.data.academicSession}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) =>
                webForm.setValue('academicSession', e?.value)
              }
              academicSessions={academicSessions}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={webForm as any}
            title="Classification"
            formKey="classification"
          >
            <ClassificationSelect
              selectValue={webForm.data.classification}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) =>
                webForm.setValue('classification', e?.value)
              }
              classifications={classifications}
              required
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
            onClick={preventNativeSubmit(onSubmit)}
            isLoading={webForm.processing}
          >
            Submit
          </Button>
        </HStack>
      }
    />
  );
}
