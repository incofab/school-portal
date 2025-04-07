import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import { preventNativeSubmit } from '@/util/util';
import { AdmissionApplication } from '@/types/models';
import { Inertia } from '@inertiajs/inertia';
import { AdmissionStatusType } from '@/types/types';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  admissionApplication: AdmissionApplication;
}

export default function AdmitStudentModal({
  isOpen,
  onSuccess,
  onClose,
  admissionApplication,
}: Props) {
  const { toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();

  const webForm = useWebForm({
    classification: '',
    admission_status: AdmissionStatusType.Admitted,
  });

  function canSubmit() {
    return webForm.data.classification;
  }

  const onSubmit = async () => {
    if (!canSubmit()) {
      toastError('Select a class, academic session and term before submitting');
      return;
    }

    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('admission-applications.update-status', [
          admissionApplication,
        ]),
        data
      );
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('admission-applications.index'));
    onClose();
    onSuccess();
  };
 
  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Admit Student'}
      bodyContent={
        <VStack>
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
