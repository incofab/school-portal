import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import FileObject from '@/components/file-dropper/file-object';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import { TermType } from '@/types/types';
import CourseSelect from '../selectors/course-select';
import { preventNativeSubmit } from '@/util/util';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function DownloadCourseResultModal({
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const webForm = useWebForm({
    academicSession: currentAcademicSession,
    term: currentTerm,
    files: [] as FileObject[],
    classification: '',
    course: '',
  });

  function canDownloadSheet() {
    return (
      webForm.data.academicSession &&
      webForm.data.course &&
      webForm.data.term &&
      webForm.data.classification
    );
  }

  const onSubmit = async () => {
    if (!canDownloadSheet()) {
      toastError(
        'Select a class, subject, academic session and term before submitting'
      );
      return;
    }
    window.location.href = instRoute('course-results.download', [webForm.data]);
    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Download Result Recording Sheet'}
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
                webForm.setValue('academicSession', e.value)
              }
              required
            />
          </FormControlBox>
          <FormControlBox
            form={webForm as any}
            title="Term"
            formKey="term"
            isRequired
          >
            <EnumSelect
              enumData={TermType}
              selectValue={webForm.data.term}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('term', e.value)}
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
              onChange={(e: any) => webForm.setValue('classification', e.value)}
              required
            />
          </FormControlBox>
          <FormControlBox form={webForm as any} title="Course" formKey="course">
            <CourseSelect
              selectValue={webForm.data.course}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('course', e.value)}
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
            Download
          </Button>
        </HStack>
      }
    />
  );
}
