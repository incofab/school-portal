import React, { useState } from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { BankAccount } from '@/types/models';
import route from '@/util/route';
import InputForm from '../forms/input-form';
import BankAccountSelect from '../selectors/bank-account-select';
import { SingleValue } from 'react-select';
import { SelectOptionType } from '@/types/types';
import useIsAdmin from '@/hooks/use-is-admin';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { generateRandomString } from '@/util/util';

interface Props {
  bankAccounts: BankAccount[];
  isInstAdmin: boolean;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
} 

export default function WithdrawFundModal({
  isOpen,
  onSuccess,
  onClose,
  bankAccounts,
  isInstAdmin,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [bankAccount, setBankAccount] = useState(
    {} as SingleValue<SelectOptionType<number>>
  );

  const webForm = useWebForm({
    bank_account_id: '',
    amount: '',
    reference: Date.now().toPrecision() + generateRandomString(15),
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        isInstAdmin
          ? instRoute('inst-withdrawals.store', {
              ...data,
              bank_account_id: bankAccount?.value,
            })
          : route('managers.withdrawals.store', {
              ...data,
              bank_account_id: bankAccount?.value,
            })
      )
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
          <FormControlBox
            form={webForm as any}
            title="Bank Account"
            formKey="bank_account_id"
          >
            <BankAccountSelect
              selectValue={bankAccount?.value}
              bankAccounts={bankAccounts ?? []}
              onChange={(e: any) => setBankAccount(e)}
            />
          </FormControlBox>

          <InputForm
            form={webForm as any}
            formKey="amount"
            title="Amount"
            isRequired
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
            Submit
          </Button>
        </HStack>
      }
    />
  );
}
