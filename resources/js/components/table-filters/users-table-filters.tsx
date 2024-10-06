import {
  Button,
  Drawer,
  DrawerBody,
  DrawerCloseButton,
  DrawerContent,
  DrawerFooter,
  DrawerHeader,
  DrawerOverlay,
  FormControl,
  FormLabel,
  VStack,
} from '@chakra-ui/react';
import React, { useState } from 'react';
import { UserRoleType } from '@/types/types';
import useQueryString from '@/hooks/use-query-string';
import { Inertia } from '@inertiajs/inertia';
import { setUrlFilterOptions } from '@/util/util';
import EnumSelect from '@/components/dropdown-select/enum-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function UsersTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    role: params.role ?? '',
  }));

  function onSave() {
    const url = new URL(window.location.href);

    setUrlFilterOptions('role', filters, url);

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
            <FormControl>
              <FormLabel>Role</FormLabel>
              <EnumSelect
                selectValue={filters.role}
                enumData={UserRoleType}
                value={filters.role}
                onChange={(e: any) =>
                  setFilters({ ...filters, role: e.currentTarget.value })
                }
              />
            </FormControl>
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
