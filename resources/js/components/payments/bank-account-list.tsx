import React from 'react';
import {
  Alert,
  AlertIcon,
  Divider,
  HStack,
  Icon,
  IconButton,
  Text,
  VStack,
} from '@chakra-ui/react';
import { LabelText } from '@/components/result-helper-components';
import { Div } from '@/components/semantic';
import { BvnNinReminderMessage } from '@/types/types';
import { ClipboardIcon } from '@heroicons/react/24/outline';
import { copyToClipboard } from '@/util/util';

export interface DisplayBankAccount {
  id: number | string;
  bank_name: string;
  account_name: string;
  account_number: string;
}

interface Props {
  accounts: DisplayBankAccount[];
  emptyMessage?: React.ReactNode;
  introText?: React.ReactNode;
}

export default function BankAccountList({
  accounts,
  emptyMessage,
  introText,
}: Props) {
  if (accounts.length < 1) {
    return <Div>{emptyMessage ?? BvnNinReminderMessage}</Div>;
  }

  return (
    <VStack align={'stretch'} spacing={2}>
      {introText ? (
        <Alert status="info" borderRadius="md">
          <AlertIcon />
          <Text fontSize="sm">{introText}</Text>
        </Alert>
      ) : null}
      {accounts.map((item) => (
        <DisplayAccountCard key={item.id} account={item} />
      ))}
    </VStack>
  );
}

function DisplayAccountCard({ account }: { account: DisplayBankAccount }) {
  return (
    <VStack
      align={'stretch'}
      spacing={1}
      w={'full'}
      p={3}
      border={'1px solid #CCCCCC'}
      borderRadius={'5px'}
    >
      <LabelText label="Bank Name" text={account.bank_name} />
      <LabelText
        label="Account No"
        lineHeight={'2rem'}
        text={
          <HStack align={'stretch'} justify={'space-between'}>
            <Text>{account.account_number}</Text>
            <IconButton
              aria-label={'Copy'}
              icon={<Icon as={ClipboardIcon} />}
              size={'sm'}
              onClick={() =>
                copyToClipboard(
                  account.account_number,
                  `Account number ${account.account_number} copied`
                )
              }
              variant={'unstyled'}
            />
          </HStack>
        }
      />
      <LabelText label="Account Name" text={account.account_name} />
    </VStack>
  );
}
