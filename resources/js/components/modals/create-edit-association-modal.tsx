import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Association } from '@/types/models';
import InputForm from '../forms/input-form';

interface Props {
  association?: Association;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function CreateEditAssociationModal({
  isOpen,
  onSuccess,
  onClose,
  association,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: association?.title ?? '',
    description: association?.description ?? '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      association
        ? web.put(instRoute('associations.update', [association]), data)
        : web.post(instRoute('associations.store'), data)
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={`${association == null ? 'Create' : 'Edit'} Division `}
      bodyContent={
        <VStack spacing={2} align={'stretch'}>
          <InputForm form={webForm as any} formKey="title" title="Title" />
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
