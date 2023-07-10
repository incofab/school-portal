import React from 'react';
import { Button, Checkbox, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import { TermType } from '@/types/types';
import { preventNativeSubmit } from '@/util/util';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function DownloadResultRecordingSheetModal({
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSession, currentTerm, usesMidTermResult } =
    useSharedProps();
  const webForm = useWebForm({
    academicSession: currentAcademicSession,
    term: currentTerm,
    classification: '',
    forMidTerm: false,
  });

  function canDownloadSheet() {
    return (
      webForm.data.academicSession &&
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
    window.location.href = instRoute('download-result-recording-sheet', [
      webForm.data,
    ]);
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
          {usesMidTermResult && (
            <FormControlBox form={webForm as any} formKey="forMidTerm" title="">
              <Checkbox
                isChecked={webForm.data.forMidTerm}
                onChange={(e) =>
                  webForm.setValue('forMidTerm', e.currentTarget.checked)
                }
              >
                For Mid-Term Result
              </Checkbox>
            </FormControlBox>
          )}
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
