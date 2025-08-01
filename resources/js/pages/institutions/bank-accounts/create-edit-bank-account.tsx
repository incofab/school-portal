import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import { BankAccount } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import useInstitutionRoute from '@/hooks/use-institution-route';
import CreateEditBankAccountForm from '@/components/create-edit-bank-account-form';

interface Props {
  bankAccount?: BankAccount;
}

export default function CreateOrEditBankAccount({ bankAccount }: Props) {
  const { instRoute } = useInstitutionRoute();

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${bankAccount ? 'Update' : 'Create'} Bank Account`}
          />
          <SlabBody>
            <CreateEditBankAccountForm
              updateUrl={instRoute('inst-bank-accounts.update', [bankAccount])}
              createUrl={instRoute('inst-bank-accounts.store')}
              bankAccount={bankAccount}
            />
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
