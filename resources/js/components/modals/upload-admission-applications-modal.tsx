import React from 'react';
import {
  FormControl,
  Button,
  FormErrorMessage,
  HStack,
  VStack,
  Text,
  Icon,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import FileDropper from '@/components/file-dropper';
import FileObject from '@/components/file-dropper/file-object';
import { FileDropperType } from '@/components/file-dropper/common';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { AdmissionForm } from '@/types/models';
import { LinkButton } from '../buttons';
import { CloudArrowDownIcon } from '@heroicons/react/24/outline';
import { generateRandomString, generateUniqueString } from '@/util/util';

interface Props {
  admissionForm: AdmissionForm;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function UploadAdmissionApplicationModal({
  admissionForm,
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    files: [] as FileObject[],
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      const formData = new FormData();
      const file = data.files[0] ?? null;
      formData.append('file', file?.file, file?.getNameWithExtension());
      formData.append('reference', generateRandomString(16));
      return web.post(
        instRoute('admission-forms.admission-applications.upload', [
          admissionForm.id,
        ]),
        formData
      );
    });

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Upload Admission Applications'}
      bodyContent={
        <VStack spacing={3}>
          <Text>{admissionForm.title}</Text>
          <LinkButton
            as={'a'}
            title="Download Template"
            leftIcon={<Icon as={CloudArrowDownIcon} />}
            href={instRoute(
              'admission-applications.download-recording-template'
            )}
          />
          <FormControl isInvalid={!!webForm.errors.files}>
            <FileDropper
              files={webForm.data.files}
              onChange={(files) => webForm.setValue('files', files)}
              multiple={false}
              accept={[FileDropperType.Excel]}
            />
            <FormErrorMessage>{webForm.errors.files}</FormErrorMessage>
          </FormControl>
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
            Upload
          </Button>
        </HStack>
      }
    />
  );
}
