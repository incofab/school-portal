import React, { useEffect } from 'react';
import {
  Button,
  HStack,
  Textarea,
  VStack,
  FormControl,
  FormLabel,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import route from '@/util/route';
import { WithdrawalStatusType } from '@/types/types';
import EnumSelect from '../dropdown-select/enum-select';
import { Withdrawal } from '@/types/models';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  withdrawal: Withdrawal | undefined;
}

export default function WithdrawStatusUpdateModal({
  isOpen,
  onSuccess,
  onClose,
  withdrawal,
}: Props) {
  const { handleResponseToast } = useMyToast();

  const webForm = useWebForm({
    withdrawal_id: withdrawal?.id,
    status: '',
    remark: '',
  });

  useEffect(() => {
    if (withdrawal?.id) {
      webForm.setValue('withdrawal_id', withdrawal.id);
    }
  }, [withdrawal]);

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.put(route('managers.withdrawals.update', [withdrawal]), data)
    );

    if (!handleResponseToast(res)) {
      return;
    }

    webForm.reset();
    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Request for withdrawal'}
      bodyContent={
        <VStack spacing={2}>
          <FormControl isInvalid={!!webForm.errors.status}>
            <FormLabel>Status</FormLabel>
            <EnumSelect
              enumData={WithdrawalStatusType}
              onChange={(e: any) => webForm.setValue('status', e.value)}
              selectValue={webForm.data.status}
              required
            />
          </FormControl>

          <FormControlBox
            form={webForm as any}
            title="Remark [optional]"
            formKey="remark"
          >
            <Textarea
              onChange={(e) =>
                webForm.setValue('remark', e.currentTarget.value)
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
            Submit
          </Button>
        </HStack>
      }
    />
  );
}
