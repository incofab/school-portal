import {
  Button,
  Drawer,
  DrawerBody,
  DrawerCloseButton,
  DrawerContent,
  DrawerFooter,
  DrawerHeader,
  DrawerOverlay,
  VStack,
} from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';
import { Inertia } from '@inertiajs/inertia';
import { setUrlFilterOptions } from '@/util/util';

interface Props {
  isOpen: boolean;
  onClose(): void;
  filters: { [key: string]: string | number | boolean };
}

export default function BaseTableFilter({
  isOpen,
  onClose,
  filters,
  children,
}: Props & PropsWithChildren) {
  function onSave() {
    const url = new URL(window.location.href);

    Object.entries(filters).forEach(([key, value]) => {
      setUrlFilterOptions(key, filters, url);
    });

    Inertia.visit(url.toString(), { preserveState: true });
    onClose();
  }

  return (
    <Drawer isOpen={isOpen} placement="right" onClose={onClose}>
      <DrawerOverlay />
      <DrawerContent>
        <DrawerCloseButton />
        <DrawerHeader>Filters</DrawerHeader>
        <DrawerBody>
          <VStack align={'stretch'} spacing={4}>
            {children}
          </VStack>
        </DrawerBody>
        <DrawerFooter borderTopWidth={1}>
          <Button variant="outline" mr={3} onClick={onClose}>
            Cancel
          </Button>
          <Button colorScheme="brand" onClick={onSave}>
            Filter
          </Button>
        </DrawerFooter>
      </DrawerContent>
    </Drawer>
  );
}
