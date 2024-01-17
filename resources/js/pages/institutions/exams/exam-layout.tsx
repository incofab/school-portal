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
  Stack,
  Icon,
  VStack,
  Divider,
  BoxProps,
  useColorMode,
} from '@chakra-ui/react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Bars3Icon, XMarkIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';
import { BreadCrumbParam } from '@/types/types';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PropsWithChildren, ReactNode } from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import ShowBreadCrumb from './component/show-bread-crumb';
import { avatarUrl } from '@/util/util';
import { TokenUser } from '@/types/models';

interface Props {
  title: string | ReactNode;
  rightElement?: string | ReactNode;
  breadCrumbItems?: BreadCrumbParam[];
  tokenUser?: TokenUser;
}

const NavLink = ({ link }: { link: BreadCrumbParam }) => {
  return (
    <Button
      as={InertiaLink}
      rounded={'md'}
      _hover={{
        textDecoration: 'underline',
        color: 'brand.300',
        cursor: 'pointer',
      }}
      href={link.href}
      variant={'link'}
      color={'brand.50'}
    >
      {link.title}
    </Button>
  );
};

export default function ExamLayout({
  title,
  rightElement,
  children,
  breadCrumbItems,
  tokenUser,
  ...props
}: Props & BoxProps & PropsWithChildren) {
  const { instRoute } = useInstitutionRoute();
  const { currentInstitution } = useSharedProps();
  const { isOpen, onOpen, onClose } = useDisclosure();

  const { colorMode, setColorMode } = useColorMode();
  if (colorMode !== 'light') {
    setColorMode('light');
  }
  const Links = [
    { title: 'Home', href: instRoute('external.home') },
    { title: 'Leader Board', href: instRoute('external.leader-board') },
  ];

  return (
    <Div background={'brand.50'} minH={'100vh'} {...props}>
      <Div
        background={'brand.700'}
        color={'white'}
        shadow={'md'}
        py={'15px'}
        px={'20px'}
      >
        <Flex h={16} alignItems={'center'} justifyContent={'space-between'}>
          <IconButton
            size={'md'}
            icon={<Icon as={isOpen ? XMarkIcon : Bars3Icon} fontSize={'2xl'} />}
            aria-label={'Open Menu'}
            display={{ md: 'none' }}
            onClick={isOpen ? onClose : onOpen}
            variant={'outline'}
            colorScheme={'whiteAlpha'}
          />
          <VStack spacing={1} alignItems={'center'}>
            <Div>
              <Text fontWeight={'bold'} fontSize={'18px'} color={'brand.100'}>
                {currentInstitution.name}
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
          </VStack>
          <HStack spacing={2}>
            {tokenUser && (
              <Avatar size={'sm'} src={avatarUrl(tokenUser.name)} />
            )}
            {/* <Flex alignItems={'center'} flexDirection={'row'} gap={2}>
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
            </Flex> */}
            <Div>{rightElement}</Div>
          </HStack>
        </Flex>

        {isOpen ? (
          <Div pb={4} display={{ md: 'none' }}>
            <Stack as={'nav'} spacing={4} alignItems={'start'}>
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
      <Div px={'8px'}>
        {title && (
          <>
            <Text
              fontWeight={'bold'}
              fontSize={'3xl'}
              color={'brand.600'}
              textAlign={'center'}
              mt={3}
            >
              {title}
            </Text>
            <Divider my={2} />{' '}
          </>
        )}
        <Div py={'20px'}>{children}</Div>
      </Div>
    </Div>
  );
}
