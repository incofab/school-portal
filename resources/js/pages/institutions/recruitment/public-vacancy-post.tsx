import React from 'react';
import {
  Avatar,
  Badge,
  Box,
  Button,
  Container,
  Divider,
  HStack,
  SimpleGrid,
  Stack,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import { Institution, VacancyPost } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  institution: Institution;
  vacancyPost: VacancyPost;
}

export default function PublicVacancyPost({ institution, vacancyPost }: Props) {
  const { instRoute } = useInstitutionRoute();
  const bg = useColorModeValue('gray.50', 'gray.900');
  const panel = useColorModeValue('white', 'gray.800');

  return (
    <Box bg={bg} minH="100vh">
      <Box bg={panel} borderBottomWidth={1}>
        <Container maxW="1050px" py={5}>
          <HStack spacing={4}>
            <Avatar src={institution.photo} name={institution.name} />
            <Box>
              <Text fontWeight="bold" fontSize="xl">
                {institution.name}
              </Text>
              <Text color="gray.600">Recruitment</Text>
            </Box>
          </HStack>
        </Container>
      </Box>
      <Container maxW="1050px" py={{ base: 8, md: 12 }}>
        <SimpleGrid columns={{ base: 1, lg: 3 }} spacing={6} alignItems="start">
          <Box
            bg={panel}
            borderWidth={1}
            rounded="lg"
            p={{ base: 5, md: 7 }}
            gridColumn={{ lg: 'span 2' }}
          >
            <HStack mb={4} wrap="wrap">
              <Badge colorScheme="blue">{vacancyPost.employment_type}</Badge>
              {vacancyPost.department && (
                <Badge colorScheme="gray">{vacancyPost.department}</Badge>
              )}
            </HStack>
            <Text fontSize={{ base: '3xl', md: '4xl' }} fontWeight="bold">
              {vacancyPost.title}
            </Text>
            {vacancyPost.summary && (
              <Text color="gray.600" fontSize="lg" mt={3}>
                {vacancyPost.summary}
              </Text>
            )}
            <Divider my={6} />
            <VStack align="stretch" spacing={6}>
              <Section title="Description" content={vacancyPost.description} />
              <Section
                title="Responsibilities"
                content={vacancyPost.responsibilities}
              />
              <Section
                title="Requirements"
                content={vacancyPost.requirements}
              />
            </VStack>
          </Box>
          <Box
            bg={panel}
            borderWidth={1}
            rounded="lg"
            p={5}
            position={{ lg: 'sticky' }}
            top={4}
          >
            <VStack align="stretch" spacing={4}>
              <Stack spacing={1}>
                <Text color="gray.500">Location</Text>
                <Text fontWeight="semibold">
                  {vacancyPost.location || 'Not specified'}
                </Text>
              </Stack>
              <Stack spacing={1}>
                <Text color="gray.500">Openings</Text>
                <Text fontWeight="semibold">
                  {vacancyPost.positions_available}
                </Text>
              </Stack>
              <Stack spacing={1}>
                <Text color="gray.500">Salary</Text>
                <Text fontWeight="semibold">
                  {vacancyPost.salary_range || 'Not specified'}
                </Text>
              </Stack>
              <Stack spacing={1}>
                <Text color="gray.500">Deadline</Text>
                <Text fontWeight="semibold">
                  {vacancyPost.application_deadline || 'Open until filled'}
                </Text>
              </Stack>
              <Button
                as={InertiaLink}
                href={instRoute('recruitment-applications.create', [
                  vacancyPost.id,
                ])}
                colorScheme="brand"
              >
                Apply Now
              </Button>
            </VStack>
          </Box>
        </SimpleGrid>
      </Container>
    </Box>
  );
}

function Section({ title, content }: { title: string; content?: string }) {
  if (!content) return null;

  return (
    <Box>
      <Text fontWeight="bold" fontSize="xl" mb={2}>
        {title}
      </Text>
      <Text whiteSpace="pre-wrap" color="gray.700">
        {content}
      </Text>
    </Box>
  );
}
