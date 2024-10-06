import React from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import SideBarToggleButton from './sidebar-toggle-button';
import {
  HStack,
  Input,
  InputGroup,
  InputLeftElement,
  Icon,
  Spacer,
  Text,
  IconButton,
  Menu,
  MenuButton,
  MenuList,
  MenuItem,
  Button,
  useColorModeValue,
  useColorMode,
} from '@chakra-ui/react';
import {
  BellIcon,
  MagnifyingGlassIcon,
  MoonIcon,
  SunIcon,
  UserIcon,
} from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import route from '@/util/route';

export default function DashboardHeader() {
  const { currentUser } = useSharedProps();
  const { colorMode, toggleColorMode } = useColorMode();
  return (
    <HStack
      background={useColorModeValue('white', 'gray.700')}
      py={1}
      boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
    >
      <SideBarToggleButton />
      <InputGroup
        maxWidth={'300px'}
        size={'xs'}
        ml={2}
        display={{ base: 'none', md: 'inline-block' }}
      >
        <InputLeftElement
          pointerEvents={'none'}
          children={<Icon as={MagnifyingGlassIcon} color="gray.300" />}
        />
        <Input type="text" placeholder="Search..." />
      </InputGroup>
      <Spacer />
      <IconButton
        aria-label={'Toggle light and dark moon'}
        icon={<Icon as={colorMode === 'dark' ? SunIcon : MoonIcon} />}
        variant={'ghost'}
        onClick={toggleColorMode}
      />
      <IconButton
        aria-label={'notifications'}
        icon={<Icon as={BellIcon} />}
        variant={'ghost'}
      />
      <Menu>
        <MenuButton
          as={Button}
          leftIcon={<Icon as={UserIcon} />}
          aria-label="Open menu"
          variant={'ghost'}
          fontWeight={'normal'}
        >
          <Text flexShrink={0} fontSize={'sm'}>
            {currentUser.last_name}
          </Text>
        </MenuButton>
        <MenuList>
          {/* <MenuItem as={InertiaLink} href={route('logout')}>Profile</MenuItem> */}
          <MenuItem as={InertiaLink} href={route('users.password.edit')}>
            Change Password
          </MenuItem>
          <MenuItem as={InertiaLink} href={route('logout')}>
            Logout
          </MenuItem>
        </MenuList>
      </Menu>
    </HStack>
  );
}
