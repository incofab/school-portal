import React from 'react';
import {
  Badge,
  Box,
  Button,
  HStack,
  Icon,
  IconButton,
  SimpleGrid,
  Stack,
  Text,
  VStack,
} from '@chakra-ui/react';
import {
  BookOpenIcon,
  DocumentIcon,
  PencilIcon,
  PhotoIcon,
  PlayCircleIcon,
  TrashIcon,
} from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import { InertiaLink } from '@inertiajs/inertia-react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import DestructivePopover from '@/components/destructive-popover';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { Library } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsStudent from '@/hooks/use-is-student';
import useIsAdmin from '@/hooks/use-is-admin';
import useIsTeacher from '@/hooks/use-is-teacher';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';

interface Props {
  libraries: PaginationResponse<Library>;
}

export default function ListLibraries({ libraries }: Props) {
  const { instRoute } = useInstitutionRoute();
  const isStudent = useIsStudent();
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  const deleteItem = async (library: Library) => {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('libraries.destroy', [library.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['libraries'] });
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="E-Library"
          rightElement={
            !isStudent && (
              <LinkButton href={instRoute('libraries.create')} title="Add" />
            )
          }
        />
        <SlabBody>
          <SimpleGrid columns={{ base: 1, md: 2, xl: 3 }} spacing={4} mb={6}>
            <SummaryCard
              label="Materials"
              value={libraries.total ?? libraries.data.length}
            />
            <SummaryCard
              label="Institution-wide"
              value={libraries.data.filter((item) => item.is_public).length}
            />
            <SummaryCard
              label="Class targeted"
              value={libraries.data.filter((item) => !item.is_public).length}
            />
          </SimpleGrid>

          <ServerPaginatedTable
            scroll
            headers={[
              {
                label: 'Material',
                value: 'title',
                render: (row) => <MaterialCell library={row} />,
              },
              {
                label: 'Audience',
                render: (row) => (
                  <Stack spacing={1}>
                    <Badge
                      w="fit-content"
                      colorScheme={row.is_public ? 'green' : 'purple'}
                    >
                      {row.is_public ? 'Everyone' : 'Selected classes'}
                    </Badge>
                    {!row.is_public && (
                      <Text fontSize="sm" color="gray.600">
                        {row.classifications
                          ?.map((classification) => classification.title)
                          .join(', ')}
                      </Text>
                    )}
                  </Stack>
                ),
              },
              {
                label: 'Source',
                render: (row) => (
                  <Stack spacing={1}>
                    <Badge
                      w="fit-content"
                      colorScheme={
                        row.source_type === 'external' ? 'blue' : 'gray'
                      }
                    >
                      {row.source_label}
                    </Badge>
                    <Text fontSize="sm" color="gray.600">
                      {row.file_size_label ?? row.file_extension ?? 'Link'}
                    </Text>
                  </Stack>
                ),
              },
              {
                label: 'Action',
                render: (row) => (
                  <HStack>
                    <Button
                      as={InertiaLink}
                      href={instRoute('libraries.show', [row.id])}
                      size="sm"
                      variant="outline"
                      leftIcon={<Icon as={BookOpenIcon} />}
                    >
                      View
                    </Button>

                    {(isAdmin || isTeacher) && (
                      <>
                        <IconButton
                          aria-label="Edit library material"
                          icon={<Icon as={PencilIcon} />}
                          as={InertiaLink}
                          href={instRoute('libraries.edit', [row.id])}
                          variant="ghost"
                          colorScheme="brand"
                        />
                        <DestructivePopover
                          label="Delete this library material"
                          onConfirm={() => deleteItem(row)}
                          isLoading={deleteForm.processing}
                        >
                          <IconButton
                            aria-label="Delete library material"
                            icon={<Icon as={TrashIcon} />}
                            variant="ghost"
                            colorScheme="red"
                          />
                        </DestructivePopover>
                      </>
                    )}
                  </HStack>
                ),
              },
            ]}
            data={libraries.data}
            keyExtractor={(row) => row.id}
            paginator={libraries}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function SummaryCard({ label, value }: { label: string; value: number }) {
  return (
    <Box borderWidth="1px" borderRadius="md" p={4} bg="white">
      <Text fontSize="sm" color="gray.500">
        {label}
      </Text>
      <Text fontSize="2xl" fontWeight="700">
        {value}
      </Text>
    </Box>
  );
}

function MaterialCell({ library }: { library: Library }) {
  const icon =
    library.material_type === 'image'
      ? PhotoIcon
      : library.material_type === 'video'
      ? PlayCircleIcon
      : DocumentIcon;

  return (
    <HStack align="start" spacing={3}>
      <Box
        w="40px"
        h="40px"
        borderRadius="md"
        bg="gray.50"
        borderWidth="1px"
        display="grid"
        placeItems="center"
        flexShrink={0}
      >
        <Icon as={icon} boxSize={5} color="brand.500" />
      </Box>
      <VStack align="start" spacing={1}>
        <Text fontWeight="700">{library.title}</Text>
        <HStack flexWrap="wrap">
          <Badge>{library.material_type}</Badge>
          {library.course?.title && (
            <Text fontSize="sm" color="gray.600">
              {library.course.title}
            </Text>
          )}
          {!library.is_published && <Badge colorScheme="orange">Draft</Badge>}
        </HStack>
      </VStack>
    </HStack>
  );
}
