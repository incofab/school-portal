import React from 'react';
import {
  Avatar,
  Badge,
  Box,
  Button,
  Container,
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
  vacancyPosts: VacancyPost[];
}

export default function PublicVacancyPosts({
  institution,
  vacancyPosts,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const bg = useColorModeValue('gray.50', 'gray.900');
  const panel = useColorModeValue('white', 'gray.800');

  return (
    <Box bg={bg} minH="100vh">
      <Box bg={panel} borderBottomWidth={1}>
        <Container maxW="1100px" py={5}>
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
      <Container maxW="1100px" py={{ base: 8, md: 12 }}>
        <Stack
          direction={{ base: 'column', md: 'row' }}
          justify="space-between"
          align={{ base: 'stretch', md: 'end' }}
          mb={8}
          spacing={5}
        >
          <Box>
            <Text fontSize={{ base: '3xl', md: '4xl' }} fontWeight="bold">
              Open Vacancies
            </Text>
            <Text color="gray.600" maxW="640px" mt={2}>
              Explore current opportunities and apply with your profile links.
            </Text>
          </Box>
          <Badge
            colorScheme="green"
            alignSelf={{ base: 'start', md: 'center' }}
          >
            {vacancyPosts.length} Open
          </Badge>
        </Stack>
        <SimpleGrid columns={{ base: 1, md: 2 }} spacing={5}>
          {vacancyPosts.map((vacancyPost) => (
            <Box
              key={vacancyPost.id}
              bg={panel}
              borderWidth={1}
              rounded="lg"
              p={5}
              shadow="sm"
            >
              <VStack align="stretch" spacing={4}>
                <Box>
                  <HStack mb={2} wrap="wrap">
                    <Badge colorScheme="blue">
                      {vacancyPost.employment_type}
                    </Badge>
                    {vacancyPost.department && (
                      <Badge colorScheme="gray">{vacancyPost.department}</Badge>
                    )}
                  </HStack>
                  <Text fontWeight="bold" fontSize="xl">
                    {vacancyPost.title}
                  </Text>
                  {vacancyPost.summary && (
                    <Text color="gray.600" mt={2} noOfLines={3}>
                      {vacancyPost.summary}
                    </Text>
                  )}
                </Box>
                <HStack justify="space-between" color="gray.600">
                  <Text>
                    {vacancyPost.location || 'Location not specified'}
                  </Text>
                  <Text>{vacancyPost.positions_available} opening(s)</Text>
                </HStack>
                <Button
                  as={InertiaLink}
                  href={instRoute('recruitment.public-show', [vacancyPost.id])}
                  colorScheme="brand"
                  alignSelf="start"
                  size="sm"
                >
                  View Vacancy
                </Button>
              </VStack>
            </Box>
          ))}
        </SimpleGrid>
        {vacancyPosts.length === 0 && (
          <Box bg={panel} borderWidth={1} rounded="lg" p={8} textAlign="center">
            <Text fontWeight="semibold">No vacancies are currently open.</Text>
          </Box>
        )}
      </Container>
    </Box>
  );
}
