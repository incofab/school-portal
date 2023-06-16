import React from 'react';
import { Button, HStack, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import useSharedProps from '@/hooks/use-shared-props';
import { generateRandomString } from '@/util/util';
import InputForm from '../forms/input-form';
import { PinPrint } from '@/types/models';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(pinPrint: PinPrint): void;
}

export default function PinPrintModal({ isOpen, onSuccess, onClose }: Props) {
  const { currentInstitution } = useSharedProps();
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    num_of_pins: '',
    comment: '',
    reference: `${currentInstitution.id} - ${generateRandomString(16)}`,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('pin-prints.store'), data);
    });

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess(res.data.pinPrint);
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Print Pin'}
      bodyContent={
        <VStack spacing={2}>
          <InputForm
            form={webForm as any}
            formKey="num_of_pins"
            title="Num of pins"
            isRequired
          />
          <FormControlBox
            form={webForm as any}
            title="Comment [optional]"
            formKey="comment"
          >
            <Textarea
              onChange={(e) =>
                webForm.setValue('comment', e.currentTarget.value)
              }
            ></Textarea>
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
            Generate
          </Button>
        </HStack>
      }
    />
  );
}
