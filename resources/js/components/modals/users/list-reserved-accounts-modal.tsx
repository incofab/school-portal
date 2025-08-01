import React from 'react';
import {
  Button,
  Divider,
  HStack,
  Icon,
  IconButton,
  Text,
  VStack,
} from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import { ReservedAccount } from '@/types/models';
import { LabelText } from '@/components/result-helper-components';
import { Div } from '@/components/semantic';
import { BvnNinReminderMessage } from '@/types/types';
import { ClipboardIcon } from '@heroicons/react/24/outline';
import { copyToClipboard } from '@/util/util';

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
        <VStack align={'stretch'} spacing={2}>
          {reservedAccounts.length < 1 ? (
            <Div>{BvnNinReminderMessage}</Div>
          ) : (
            <>
              <Text>
                Fund your account by sending money to any of these account
                numbers. All means of payment including POS, Bank transfer,
                direct deposits, etc are accepted
              </Text>
              <Divider my={2} />
              {reservedAccounts.map((item) => (
                <DisplayAccount key={item.id} reservedAccount={item} />
              ))}
            </>
          )}
        </VStack>
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

function DisplayAccount({
  reservedAccount,
}: {
  reservedAccount: ReservedAccount;
}) {
  return (
    <VStack
      align={'stretch'}
      spacing={1}
      w={'full'}
      p={3}
      border={'1px solid #CCCCCC'}
      borderRadius={'5px'}
    >
      <LabelText label="Bank Name" text={reservedAccount.bank_name} />
      <LabelText
        label="Account No"
        lineHeight={'2rem'}
        text={
          <HStack align={'stretch'} justify={'space-between'}>
            <Text>{reservedAccount.account_number}</Text>
            <IconButton
              aria-label={'Copy'}
              icon={<Icon as={ClipboardIcon} />}
              size={'sm'}
              onClick={() =>
                copyToClipboard(
                  reservedAccount.account_number,
                  `Account number ${reservedAccount.account_number} copied`
                )
              }
              variant={'unstyled'}
            />
          </HStack>
        }
      />
      <LabelText label="Account Name" text={reservedAccount.account_name} />
    </VStack>
  );
}
