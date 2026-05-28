import React from 'react';
import {
  Badge,
  Box,
  Button,
  HStack,
  Icon,
  SimpleGrid,
  Stack,
  Text,
  VStack,
} from '@chakra-ui/react';
import {
  ArrowDownTrayIcon,
  ArrowTopRightOnSquareIcon,
  DocumentIcon,
} from '@heroicons/react/24/outline';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Library } from '@/types/models';

interface Props {
  library: Library;
}

export default function ShowLibrary({ library }: Props) {
  const actionLabel =
    library.source_type === 'external' ? 'Open Link' : 'Open File';

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Library Material" />
        <SlabBody>
          <VStack align="stretch" spacing={6}>
            <Box borderWidth="1px" borderRadius="md" p={5} bg="white">
              <HStack align="start" spacing={4}>
                <Box
                  w="56px"
                  h="56px"
                  borderRadius="md"
                  bg="gray.50"
                  borderWidth="1px"
                  display="grid"
                  placeItems="center"
                  flexShrink={0}
                >
                  <Icon as={DocumentIcon} boxSize={7} color="brand.500" />
                </Box>
                <Stack flex={1} spacing={2}>
                  <HStack flexWrap="wrap">
                    <Badge colorScheme="brand">{library.material_type}</Badge>
                    <Badge
                      colorScheme={
                        library.source_type === 'external' ? 'blue' : 'gray'
                      }
                    >
                      {library.source_label}
                    </Badge>
                    <Badge colorScheme={library.is_public ? 'green' : 'purple'}>
                      {library.is_public ? 'Everyone' : 'Selected classes'}
                    </Badge>
                  </HStack>
                  <Text fontSize="2xl" fontWeight="800">
                    {library.title}
                  </Text>
                  {library.description && (
                    <Text color="gray.700">{library.description}</Text>
                  )}
                  {library.access_url && (
                    <Button
                      as="a"
                      href={library.access_url}
                      target="_blank"
                      rel="noreferrer"
                      w="fit-content"
                      colorScheme="brand"
                      leftIcon={
                        <Icon
                          as={
                            library.source_type === 'external'
                              ? ArrowTopRightOnSquareIcon
                              : ArrowDownTrayIcon
                          }
                        />
                      }
                    >
                      {actionLabel}
                    </Button>
                  )}
                </Stack>
              </HStack>
            </Box>

            <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
              <InfoBox label="Course" value={library.course?.title} />
              <InfoBox label="File name" value={library.file_name} />
              <InfoBox label="File size" value={library.file_size_label} />
            </SimpleGrid>

            <Box borderWidth="1px" borderRadius="md" p={5}>
              <Text fontWeight="700" mb={3}>
                Audience
              </Text>
              {library.is_public ? (
                <Text color="gray.700">
                  This material is available to every student in the
                  institution.
                </Text>
              ) : (
                <HStack flexWrap="wrap">
                  {library.classifications?.map((classification) => (
                    <Badge key={classification.id} colorScheme="purple">
                      {classification.title}
                    </Badge>
                  ))}
                </HStack>
              )}
            </Box>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function InfoBox({ label, value }: { label: string; value?: string | null }) {
  return (
    <Box borderWidth="1px" borderRadius="md" p={4}>
      <Text fontSize="sm" color="gray.500">
        {label}
      </Text>
      <Text fontWeight="700">{value || 'N/A'}</Text>
    </Box>
  );
}
