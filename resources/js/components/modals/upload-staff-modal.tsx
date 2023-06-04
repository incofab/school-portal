import React from 'react';
import {
  FormControl,
  Button,
  FormErrorMessage,
  HStack,
  VStack,
  Icon,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import FileDropper from '@/components/file-dropper';
import FileObject from '@/components/file-dropper/file-object';
import { FileDropperType } from '@/components/file-dropper/common';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import { LinkButton } from '../buttons';
import { CloudArrowDownIcon } from '@heroicons/react/24/solid';
import { InstitutionUserType } from '@/types/types';
import EnumSelect from '../dropdown-select/enum-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function UploadStaffModal({
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    files: [] as FileObject[],
    role: InstitutionUserType.Teacher,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      const formData = new FormData();
      const file = data.files[0] ?? null;
      formData.append('file', file?.file, file?.getNameWithExtension());
      return web.post(instRoute('users.upload', [data.role]), formData);
    });

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Upload Staff'}
      bodyContent={
        <VStack>
          <LinkButton
            title="Download Template"
            leftIcon={<Icon as={CloudArrowDownIcon} />}
            href={instRoute('users.download-recording-template')}
          />
          <FormControlBox
            form={webForm as any}
            title="Role"
            formKey="Select Role"
          >
            <EnumSelect
              enumData={InstitutionUserType}
              selectValue={webForm.data.role}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('role', e.value)}
              required
            />
          </FormControlBox>
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
