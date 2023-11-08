// MobileNav.tsx

import {
  Button,
  Box,
  Collapse,
  Flex,
  IconButton,
  useDisclosure,
  useColorModeValue,
  Stack,
  Icon,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  AcademicCapIcon,
  Bars3Icon,
  HomeIcon,
  XMarkIcon,
} from '@heroicons/react/24/solid';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {}
export default function ExternalHomeNav({}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { isOpen, onToggle } = useDisclosure();
  const bgColor = useColorModeValue('white', 'gray.800');

  return (
    <Flex
      w="100%"
      alignItems="center"
      justifyContent="space-between"
      p={4}
      bg={bgColor}
      color={useColorModeValue('gray.600', 'white')}
    >
      <IconButton
        size="md"
        icon={isOpen ? <Icon as={XMarkIcon} /> : <Icon as={Bars3Icon} />}
        aria-label="Open Menu"
        display={{ md: 'none' }}
        onClick={onToggle}
      />
      {/* <Logo /> */}
      <Collapse in={isOpen}>
        <Box pb={4} display={{ md: 'none' }}>
          <Stack spacing={4}>
            <Button
              as={InertiaLink}
              variant={'link'}
              leftIcon={<Icon as={HomeIcon} />}
              href={instRoute('external.home')}
            >
              Home
            </Button>
            <Button
              as={InertiaLink}
              variant={'link'}
              leftIcon={<Icon as={AcademicCapIcon} />}
              href={instRoute('external.leader-board')}
            >
              Leader Board
            </Button>
          </Stack>
        </Box>
      </Collapse>
    </Flex>
  );
}
