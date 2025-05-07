import React, { useEffect, useState } from 'react';
import { Button, HStack, VStack, Text } from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import { Withdrawal } from '@/types/models';
import useWebForm from '@/hooks/use-web-form';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  withdrawal: Withdrawal | undefined;
}

export default function WithdrawalOverviewModal({
  isOpen,
  onSuccess,
  onClose,
  withdrawal,
}: Props) {
  const [remark, setRemark] = useState<string>();

  useEffect(() => {
    if (withdrawal) {
      setRemark(withdrawal.remark);
    }
  }, [withdrawal]);

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Payment Overview'}
      bodyContent={remark}
      footerContent={
        <HStack spacing={2}>
          <Button colorScheme={'brand'} variant={'ghost'} onClick={onClose}>
            Close
          </Button>
        </HStack>
      }
    />
  );
}
