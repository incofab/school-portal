import {
  Flex,
  Avatar,
  HStack,
  Text,
  IconButton,
  Button,
  Menu,
  MenuButton,
  MenuList,
  MenuItem,
  MenuDivider,
  useDisclosure,
  useColorModeValue,
  Stack,
  Icon,
} from '@chakra-ui/react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Bars3Icon, XMarkIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';
import { BreadCrumbParam } from '@/types/types';
import { InertiaLink } from '@inertiajs/inertia-react';
import ShowBreadCrumb from './show-bread-crumb';
import { PropsWithChildren, ReactNode } from 'react';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  title: string | ReactNode;
  rightElement?: string | ReactNode;
  breadCrumbItems?: BreadCrumbParam[];
}

const NavLink = ({ link }: { link: BreadCrumbParam }) => {
  return (
    <Button
      as={InertiaLink}
      rounded={'md'}
      _hover={{
        textDecoration: 'none',
        bg: useColorModeValue('gray.200', 'gray.700'),
      }}
      href={link.href}
      variant={'link'}
    >
      {link.title}
    </Button>
  );
};

export default function ExternalHomeNav({
  title,
  rightElement,
  children,
  breadCrumbItems,
}: Props & PropsWithChildren) {
  const { instRoute } = useInstitutionRoute();
  const { currentInstitution } = useSharedProps();
  const { isOpen, onOpen, onClose } = useDisclosure();

  const Links = [
    { title: 'Home', href: instRoute('external.home') },
    { title: 'Leader Board', href: instRoute('external.leader-board') },
  ];

  return (
    <>
      <Div px={4} background={'brand.50'} minH={'100vh'}>
        <Flex h={16} alignItems={'center'} justifyContent={'space-between'}>
          <IconButton
            size={'md'}
            icon={isOpen ? <Icon as={XMarkIcon} /> : <Icon as={Bars3Icon} />}
            aria-label={'Open Menu'}
            display={{ md: 'none' }}
            onClick={isOpen ? onClose : onOpen}
          />
          <HStack spacing={8} alignItems={'center'}>
            <Div>
              <Text fontWeight={'bold'} fontSize={'18px'} color={'brand.100'}>
                {currentInstitution.name}
              </Text>
              <Text fontWeight={'bold'} fontSize={'18px'}>
                {title}
              </Text>
            </Div>
            <HStack
              as={'nav'}
              spacing={4}
              display={{ base: 'none', md: 'flex' }}
            >
              {Links.map((link) => (
                <NavLink key={link.title} link={link} />
              ))}
            </HStack>
          </HStack>
          <Flex alignItems={'center'} flexDirection={'row'} gap={2}>
            <Menu>
              <MenuButton
                as={Button}
                rounded={'full'}
                variant={'link'}
                cursor={'pointer'}
                minW={0}
              >
                <Avatar
                  size={'sm'}
                  src={
                    'https://images.unsplash.com/photo-1493666438817-866a91353ca9?ixlib=rb-0.3.5&q=80&fm=jpg&crop=faces&fit=crop&h=200&w=200&s=b616b2c5b373a80ffc9636ba24f7a4a9'
                  }
                />
              </MenuButton>
              <MenuList>
                <MenuItem>Link 1</MenuItem>
                <MenuItem>Link 2</MenuItem>
                <MenuDivider />
                <MenuItem>Link 3</MenuItem>
              </MenuList>
            </Menu>
            <Div>{rightElement}</Div>
          </Flex>
        </Flex>

        {isOpen ? (
          <Div pb={4} display={{ md: 'none' }}>
            <Stack as={'nav'} spacing={4}>
              {Links.map((link) => (
                <NavLink key={link.title} link={link} />
              ))}
            </Stack>
          </Div>
        ) : null}
      </Div>

      <ShowBreadCrumb
        breadCrumbItems={[
          { title: 'Home', href: instRoute('external.home') },
          ...(breadCrumbItems ?? []),
        ]}
      />
      <Div py={'20px'}>{children}</Div>
    </>
  );
}
