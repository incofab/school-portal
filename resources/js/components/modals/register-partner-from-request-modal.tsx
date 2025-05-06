import React from 'react';
import { Button, HStack, Input, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { PartnerRegistrationRequest } from '@/types/models';
import route from '@/util/route';

interface Props {
  partnerRegistrationRequest: PartnerRegistrationRequest;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function RegisterPartnerFromRequestModal({
  isOpen,
  onSuccess,
  onClose,
  partnerRegistrationRequest,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    commission: '',
    referral_email: partnerRegistrationRequest.referral?.user?.email ?? '',
    referral_commission: '0',
  });

  const onSubmit = async () => {
    let res = await webForm.submit((data, web) => {
      return web.post(
        route('managers.partner-registration-requests.onboard', [
          partnerRegistrationRequest.id,
        ]),
        data
      );
    });

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Register Partner'}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox
            form={webForm}
            title="Commission"
            formKey="commission"
          >
            <Input
              type="number"
              onChange={(e) =>
                webForm.setValue('commission', e.currentTarget.value)
              }
              value={webForm.data.commission}
            />
          </FormControlBox>

          {partnerRegistrationRequest.referral && (
            <FormControlBox
              form={webForm}
              title="Referral Commission"
              formKey="referral_commission"
            >
              <Input
                type="number"
                onChange={(e) =>
                  webForm.setValue('referral_commission', e.currentTarget.value)
                }
                value={webForm.data.referral_commission}
              />
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
