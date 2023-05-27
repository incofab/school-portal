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
import { Nullable, SelectOptionType, UserRoleType } from '@/types/types';
import useQueryString from '@/hooks/use-query-string';
import { Inertia } from '@inertiajs/inertia';
import { setUrlFilterOptions } from '@/util/util';
import HostelSelect from '@/components/hostel-select';
import UserSelect from '@/components/user-select';
import useIsAdmin from '@/hooks/use-is-admin';
import ReactSelect from '@/components/react-select';
import { AcademicSession } from '@/types/models';

interface Props {
  isOpen: boolean;
  onClose(): void;
  academicSessions: AcademicSession[];
}

export default function HostelUsersTableFilters({
  isOpen,
  onClose,
  academicSessions,
}: Props) {
  const { params } = useQueryString();
  const isAdmin = useIsAdmin();

  const [filters, setFilters] = useState(() => ({
    hostel: params.hostel ?? '',
    academicSession: params.academicSession ?? '',
    user: (params.user
      ? {
          label: params.userLabel,
          value: params.user,
        }
      : null) as Nullable<SelectOptionType<string>>,
  }));

  function onSave() {
    const url = new URL(window.location.href);

    setUrlFilterOptions('user', filters, url);
    setUrlFilterOptions('hostel', filters, url);
    setUrlFilterOptions('academicSession', filters, url);

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
            {isAdmin && (
              <FormControl>
                <FormLabel>User</FormLabel>
                <UserSelect
                  roles={[UserRoleType.Student]}
                  value={filters.user}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e) => setFilters({ ...filters, user: e })}
                  required
                />
              </FormControl>
            )}
            <FormControl>
              <FormLabel>Hostel</FormLabel>
              <HostelSelect
                selectValue={filters.hostel}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) =>
                  setFilters({ ...filters, hostel: e?.value })
                }
              />
            </FormControl>
            <FormControl>
              <FormLabel>Academic Session</FormLabel>
              <ReactSelect
                data={{ main: academicSessions, label: 'title', value: 'id' }}
                selectValue={filters.academicSession}
                onChange={(e: any) =>
                  setFilters({ ...filters, academicSession: e?.value })
                }
                isMulti={false}
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
