import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import InputForm from '../forms/input-form';
import { ReceiptType } from '@/types/models';
import { Nullable } from '@/types/types';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  receiptType: Nullable<ReceiptType>;
}

export default function CreateEditReceiptTypeModal({
  isOpen,
  onSuccess,
  onClose,
  receiptType,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: receiptType?.title ?? '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      receiptType
        ? web.put(instRoute('receipt-types.update', [receiptType]), data)
        : web.post(instRoute('receipt-types.store'), data)
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
      headerContent={`${receiptType == null ? 'Create' : 'Edit'} Fee Category`}
      bodyContent={
        <VStack spacing={2}>
          <InputForm form={webForm as any} formKey="title" title=" Title" />
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
