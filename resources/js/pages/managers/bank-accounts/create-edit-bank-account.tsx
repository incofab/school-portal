import React from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { BankAccount, Course } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import route from '@/util/route';
import useIsAdmin from '@/hooks/use-is-admin';
import useIsPartner from '@/hooks/use-is-partner';

interface Props {
  bankAccount?: BankAccount;
}

export default function CreateOrEditBankAccount({ bankAccount }: Props) {
  // const isAdmin = useIsAdmin();
  const isPartner = useIsPartner();
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    bank_name: bankAccount?.bank_name ?? '',
    account_name: bankAccount?.account_name ?? '',
    account_number: bankAccount?.account_number ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return bankAccount
        ? web.put(route('managers.bank-accounts.update', [bankAccount]), data)
        : web.post(route('managers.bank-accounts.store'), data);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(route('managers.bank-accounts.index'));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${bankAccount ? 'Update' : 'Create'} Bank Account`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={webForm as any}
                formKey="bank_name"
                title="Bank Name"
                isRequired
              />

              <InputForm
                form={webForm as any}
                formKey="account_name"
                title="Account Name"
                isRequired
              />

              <InputForm
                form={webForm as any}
                formKey="account_number"
                title="Account Number"
                type="number"
                isRequired
              />

              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
