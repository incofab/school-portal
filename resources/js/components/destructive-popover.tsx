import React, { PropsWithChildren } from 'react';
import {
  Button,
  HStack,
  Popover,
  PopoverArrow,
  PopoverBody,
  PopoverCloseButton,
  PopoverContent,
  PopoverFooter,
  PopoverTrigger,
  Portal,
} from '@chakra-ui/react';

interface Props {
  label: string;
  onConfirm(onClose: () => void): void;
  isLoading?: boolean;
  positiveButtonLabel?: string;
  negativeButtonLabel?: string;
}

export default function DestructivePopover({
  label,
  onConfirm,
  children,
  isLoading,
  positiveButtonLabel,
  negativeButtonLabel,
}: PropsWithChildren<Props>) {
  return (
    <Popover>
      {({ onClose }) => (
        <>
          <PopoverTrigger>{children}</PopoverTrigger>
          <Portal>
            <PopoverContent>
              <PopoverArrow />
              <PopoverCloseButton />
              <PopoverBody>{label}</PopoverBody>
              <PopoverFooter display={'flex'} justifyContent={'flex-end'}>
                <HStack spacing={1}>
                  <Button size={'sm'} onClick={onClose} variant={'ghost'}>
                    {negativeButtonLabel ?? 'Cancel'}
                  </Button>
                  <Button
                    size={'sm'}
                    colorScheme={'red'}
                    onClick={() => onConfirm(onClose)}
                    isLoading={isLoading}
                  >
                    {positiveButtonLabel ?? 'Delete'}
                  </Button>
                </HStack>
              </PopoverFooter>
            </PopoverContent>
          </Portal>
        </>
      )}
    </Popover>
  );
}
