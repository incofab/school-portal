import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { Fee } from '@/types/models';
import { generateRandomString } from '@/util/util';
import FeeSelect from '../selectors/fee-select';
import EnumSelect from '../dropdown-select/enum-select';
import { NotificationChannelsType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { AxiosInstance } from 'axios';

interface Props {
  selectedFee?: Fee;
  fees: Fee[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function CreateFeeReminderModal({
  isOpen,
  onSuccess,
  onClose,
  selectedFee,
  fees,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    fee_id: selectedFee?.id ?? '',
    channel: NotificationChannelsType.Sms,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) =>
      web.post(instRoute('payment-notifications.store'), {
        ...data,
        reference: generateRandomString(15, true),
      })
    );

    if (!handleResponseToast(res)) return;

    webForm.reset();
    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Record outstanding debt owed by Institution Group'}
      bodyContent={
        <VStack spacing={4}>
          <FormControlBox form={webForm as any} title="Fee" formKey="fee_id">
            <FeeSelect
              selectValue={webForm.data.fee_id}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('fee_id', e?.value)}
              fees={fees}
              required
            />
          </FormControlBox>

          <FormControlBox
            isRequired
            form={webForm as any}
            title="Notification Channel"
            formKey="channel"
          >
            <EnumSelect
              enumData={NotificationChannelsType}
              onChange={(e: any) => webForm.setValue('channel', e.value)}
              selectValue={webForm.data.channel}
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
