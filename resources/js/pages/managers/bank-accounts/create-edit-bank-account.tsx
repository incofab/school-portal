import React from 'react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import { BankAccount } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import route from '@/util/route';
import CreateEditBankAccountForm from '@/components/create-edit-bank-account-form';

interface Props {
  bankAccount?: BankAccount;
}

export default function CreateOrEditBankAccount({ bankAccount }: Props) {
  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${bankAccount ? 'Update' : 'Create'} Bank Account`}
          />
          <SlabBody>
            <CreateEditBankAccountForm
              updateUrl={
                bankAccount
                  ? route('managers.bank-accounts.update', [bankAccount])
                  : ''
              }
              createUrl={route('managers.bank-accounts.store')}
              bankAccount={bankAccount}
            />
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
