import React from 'react';
import { Button, HStack, Input, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { RegistrationRequest } from '@/types/models';
import route from '@/util/route';

interface Props {
  registrationRequest: RegistrationRequest;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function RegisterInstitutionGroupFromRequestModal({
  isOpen,
  onSuccess,
  onClose,
  registrationRequest,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    name: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        route('managers.registration-requests.institution-groups.store', [
          registrationRequest.id,
        ]),
        data
      )
    );

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    webForm.setData({ name: '' });
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Register Institution Group'}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox form={webForm as any} title="Name" formKey="name">
            <Input
              value={webForm.data.name}
              onChange={(e) => webForm.setValue('name', e.currentTarget.value)}
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
