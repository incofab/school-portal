import React from 'react';
import { Button, HStack } from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import { ReservedAccount } from '@/types/models';
import BankAccountList from '@/components/payments/bank-account-list';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess?(): void;
  reservedAccounts: ReservedAccount[];
}

export default function ListReservedAccountsModal({
  isOpen,
  onClose,
  reservedAccounts,
}: Props) {
  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Bank Accounts'}
      bodyContent={
        <BankAccountList
          accounts={reservedAccounts}
          introText="Fund your account by sending money to any of these account numbers. All means of payment including POS, bank transfer, and direct deposits are accepted."
        />
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Okay
          </Button>
        </HStack>
      }
    />
  );
}
