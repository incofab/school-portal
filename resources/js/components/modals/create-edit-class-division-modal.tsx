import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import InputForm from '../forms/input-form';
import { ClassDivision } from '@/types/models';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  classDivision?: ClassDivision;
}

export default function CreateEditClassDivisionModal({
  isOpen,
  onSuccess,
  onClose,
  classDivision,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: classDivision?.title ?? '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      classDivision
        ? web.put(
            instRoute('class-divisions.update', [classDivision]),
            data
          )
        : web.post(instRoute('class-divisions.store'), data)
    );

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Create Class Division'}
      bodyContent={
        <VStack spacing={2}>
          <InputForm
            form={webForm as any}
            formKey="title"
            title="Class Division Title"
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
            Create
          </Button>
        </HStack>
      }
    />
  );
}
