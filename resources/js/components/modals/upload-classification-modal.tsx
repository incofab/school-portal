import React from 'react';
import {
  FormControl,
  Button,
  FormErrorMessage,
  HStack,
  Icon,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import FileDropper from '@/components/file-dropper';
import FileObject from '@/components/file-dropper/file-object';
import { FileDropperType } from '@/components/file-dropper/common';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { CloudArrowDownIcon } from '@heroicons/react/24/solid';
import { Div } from '../semantic';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function UploadClassificationModal({
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    files: [] as FileObject[],
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      const formData = new FormData();
      const file = data.files[0] ?? null;
      formData.append('file', file?.file, file?.getNameWithExtension());
      return web.post(instRoute('classifications.upload'), formData);
    });

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Upload Classes'}
      bodyContent={
        <Div>
          <Button
            as={'a'}
            href={instRoute('classifications.download')}
            leftIcon={<Icon as={CloudArrowDownIcon} />}
            variant={'solid'}
            colorScheme={'brand'}
            mb={3}
          >
            Download Classes
          </Button>
          <FormControl isInvalid={!!webForm.errors.files}>
            <FileDropper
              files={webForm.data.files}
              onChange={(files) => webForm.setValue('files', files)}
              multiple={false}
              accept={[FileDropperType.Excel]}
            />
            <FormErrorMessage>{webForm.errors.files}</FormErrorMessage>
          </FormControl>
        </Div>
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
