import React from 'react';
import { Checkbox, FormControl, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import { BankAccount } from '@/types/models';
import { BrandButton, FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import BankSelect from '@/components/selectors/bank-select';
import route from '@/util/route';

interface Props {
  bankAccount?: BankAccount;
  createUrl: string;
  updateUrl: string;
}

export default function CreateEditBankAccountForm({
  bankAccount,
  createUrl,
  updateUrl,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const validateWebForm = useWebForm({});
  const webForm = useWebForm({
    bank_account_id: bankAccount?.id ?? '',
    bank_code: bankAccount?.bank_code ?? '',
    bank_name: bankAccount?.bank_name ?? '',
    account_name: bankAccount?.account_name ?? '',
    account_number: bankAccount?.account_number ?? '',
    is_primary: bankAccount?.is_primary ?? false,
  });

  async function validateAccountNumber() {
    if (!webForm.data.account_number || !webForm.data.bank_code) {
      toastError('Select a bank and enter account number to verify');
      return;
    }
    const res = await validateWebForm.submit((data, web) => {
      return web.post(
        route('bank-accounts.validate', {
          bank_code: webForm.data.bank_code,
          account_number: webForm.data.account_number,
        })
      );
    });

    if (!handleResponseToast(res)) return;

    webForm.setValue('account_name', res.data.account_name);
  }

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return bankAccount ? web.put(updateUrl, data) : web.post(createUrl, data);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('inst-bank-accounts.index'));
  };

  return (
    <VStack spacing={4} align={'stretch'}>
      <FormControlBox
        form={webForm as any}
        title="Bank Name"
        formKey="bank_code"
      >
        <BankSelect
          selectValue={webForm.data.bank_code}
          isMulti={false}
          isClearable={true}
          onChange={(e: any) =>
            webForm.setData({
              ...webForm.data,
              bank_code: e?.value,
              bank_name: e?.label,
            })
          }
          required
        />
      </FormControlBox>

      <HStack align={'end'}>
        <InputForm
          form={webForm as any}
          formKey="account_number"
          title="Account Number"
          type="number"
          isRequired
        />
        <BrandButton
          title="Validate"
          onClick={() => validateAccountNumber()}
          size="sm"
          isLoading={validateWebForm.processing}
        />
      </HStack>

      <InputForm
        form={webForm as any}
        formKey="account_name"
        title="Account Name"
        isDisabled={true}
      />

      <FormControl>
        <Checkbox
          isChecked={webForm.data.is_primary}
          onChange={(e) =>
            webForm.setValue('is_primary', e.currentTarget.checked)
          }
          size={'md'}
          colorScheme="brand"
        >
          Make this your primary bank account
        </Checkbox>
      </FormControl>

      <FormControl>
        <FormButton isLoading={webForm.processing} onClick={() => submit()} />
      </FormControl>
    </VStack>
  );
}
