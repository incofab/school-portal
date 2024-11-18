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
  isLoading?: boolean;
  negativeButtonLabel?: string;
}

export default function InfoPopover({
  label,
  children,
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
                    {negativeButtonLabel ?? 'Close'}
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
