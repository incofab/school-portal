import React from 'react';
import {
  Avatar,
  Box,
  Button,
  Container,
  HStack,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import { Institution, RecruitmentApplication } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  institution: Institution;
  recruitmentApplication: RecruitmentApplication;
}

export default function RecruitmentApplicationSuccess({
  institution,
  recruitmentApplication,
}: Props) {
  const { instRoute } = useInstitutionRoute();

  return (
    <Box bg={useColorModeValue('gray.50', 'gray.900')} minH="100vh">
      <Container maxW="720px" py={{ base: 10, md: 16 }}>
        <Box
          bg={useColorModeValue('white', 'gray.800')}
          borderWidth={1}
          rounded="lg"
          p={{ base: 6, md: 10 }}
          textAlign="center"
        >
          <VStack spacing={5}>
            <Avatar src={institution.photo} name={institution.name} size="lg" />
            <Box>
              <Text fontSize={{ base: '2xl', md: '3xl' }} fontWeight="bold">
                Application Submitted
              </Text>
              <Text color="gray.600" mt={2}>
                Your application for{' '}
                {recruitmentApplication.vacancy_post?.title}
                has been received.
              </Text>
            </Box>
            <Box bg="gray.50" borderWidth={1} rounded="md" p={4} width="full">
              <Text color="gray.500">Application Number</Text>
              <Text fontWeight="bold" fontSize="xl">
                {recruitmentApplication.application_no}
              </Text>
            </Box>
            <HStack>
              <Button
                as={InertiaLink}
                href={instRoute('recruitment.public-index')}
                colorScheme="brand"
              >
                View Other Vacancies
              </Button>
            </HStack>
          </VStack>
        </Box>
      </Container>
    </Box>
  );
}
