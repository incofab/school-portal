import React from 'react';
import { Classification, Student, User } from '@/types/models';
import {
  HStack,
  IconButton,
  Icon,
  Button,
  Menu,
  MenuButton,
  MenuList,
  MenuItem,
  useColorModeValue,
  Avatar,
  VStack,
  Text,
  SimpleGrid,
  Stack,
  Grid,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  BookOpenIcon,
  EllipsisVerticalIcon,
  TrashIcon,
} from '@heroicons/react/24/solid';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import { Div } from '@/components/semantic';
import { BrandButton, LinkButton } from '@/components/buttons';

interface Dependent extends Student {
  user: User;
  student_id: number;
  classification: Classification;
}

interface Props {
  dependents: PaginationResponse<Dependent>;
}

export default function ListDependents({ dependents }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const deleteForm = useWebForm({ id: 0 });

  async function deleteItem(obj: Dependent) {
    deleteForm.setValue('id', obj.id);
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('guardians.remove-dependent', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['dependents'] });
  }

  const headers: ServerPaginatedTableHeader<Dependent>[] = [
    {
      label: 'Name',
      value: 'full_name',
      render: (row) => <DisplayUserFullname user={row.user} />,
    },
    {
      label: 'Class',
      value: 'classification.title',
    },
    {
      label: 'Student Id',
      value: 'code',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <Button
            as={InertiaLink}
            leftIcon={<Icon as={BookOpenIcon} />}
            href={instRoute('term-results.index', [row.user_id])}
            variant={'link'}
            colorScheme={'brand'}
          >
            Results
          </Button>
          <DestructivePopover
            label={`Remove ${row.user.full_name} as your child/ward?`}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>

          <Menu>
            <MenuButton
              as={IconButton}
              aria-label={'open file menu'}
              icon={<Icon as={EllipsisVerticalIcon} />}
              size={'sm'}
              variant={'ghost'}
            />
            <MenuList>
              <MenuItem
                as={InertiaLink}
                href={instRoute('students.receipts.index', [row.student_id])}
              >
                Fees & Receipts
              </MenuItem>
            </MenuList>
          </Menu>
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="My Students" />
        <SlabBody>
          <SimpleGrid columns={{ base: 1, md: 2, lg: 3 }} spacing={6}>
            {dependents.data.map((dependant) => (
              <StudentCard
                key={dependant.student_id}
                dependant={dependant}
                deleteItem={deleteItem}
                isLoading={deleteForm.processing}
              />
            ))}
          </SimpleGrid>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function StudentCard({
  dependant,
  deleteItem,
  isLoading,
}: {
  dependant: Dependent;
  deleteItem: (obj: Dependent) => void;
  isLoading: boolean;
}) {
  const { instRoute } = useInstitutionRoute();

  return (
    <Div
      bg={useColorModeValue('white', 'gray.900')}
      border="1px solid"
      borderColor={'green.100'}
      borderRadius="2xl"
      p={4}
      boxShadow="md"
      w="full"
    >
      <HStack align="stretch" justify="space-between" mb={4} spacing={3}>
        <Avatar
          size="lg"
          name={dependant.user.full_name}
          src={dependant.user.photo_url}
          border="2px solid"
          borderColor="gray.300"
        />
        <VStack align="start" spacing={1} w={'full'}>
          <Text fontSize="lg" fontWeight="bold" noOfLines={1}>
            {dependant.user.full_name}
          </Text>
          <Text fontSize="sm" color="gray.500">
            Class: {dependant.classification.title}
          </Text>
          <Text fontSize="sm" color="gray.500">
            Student Id: {dependant.code}
          </Text>
        </VStack>
      </HStack>

      <Grid gridColumn={2} gap={4}>
        <LinkButton
          title="Term Results"
          href={instRoute('term-results.index', [dependant.user_id])}
        />
        <LinkButton
          title="Fees & Receipts"
          href={instRoute('students.receipts.index', [dependant.student_id])}
        />
        <LinkButton
          title="Session Results"
          href={instRoute('students.session-results.index', [
            dependant.student_id,
          ])}
          colorScheme={'blue'}
        />
        <DestructivePopover
          label={`Remove ${dependant.user.full_name} as your child/ward?`}
          onConfirm={() => deleteItem(dependant)}
          isLoading={isLoading}
        >
          <BrandButton
            aria-label={'Delete'}
            leftIcon={<Icon as={TrashIcon} />}
            variant={'solid'}
            colorScheme={'red'}
            title="Delete"
          />
        </DestructivePopover>
      </Grid>
    </Div>
  );
}
