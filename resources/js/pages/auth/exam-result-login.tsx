import {
  Badge,
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  Icon,
  Input,
  SimpleGrid,
  Text,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import { useForm } from '@inertiajs/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  ArrowLeftIcon,
  IdentificationIcon,
  TrophyIcon,
} from '@heroicons/react/24/outline';
import CenteredLayout from '@/components/centered-layout';
import { BrandButton, LinkButton } from '@/components/buttons';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import { InstitutionGroup } from '@/types/models';
import { Div } from '@/components/semantic';
import { Inertia } from '@inertiajs/inertia';

export default function ExamResultLogin({
  institutionGroup,
}: {
  institutionGroup?: InstitutionGroup;
}) {
  const { data, setData, post, processing, errors } = useForm({
    exam_no: '',
  });

  const handleSubmit = () => {
    // post(route('exam-results.store'));
    Inertia.visit(route('exam-results.show', data.exam_no));
  };

  return (
    <CenteredLayout
      title="Check Exam Result"
      bgImage={institutionGroup?.banner}
      boxProps={{ maxW: 'lg', opacity: 0.96 }}
      rightHeader={
        <LinkButton
          title="Main Login"
          href={route('login')}
          variant="outline"
          rightIcon={<Icon as={ArrowLeftIcon} />}
        />
      }
    >
      <VStack
        spacing={6}
        align="stretch"
        as="form"
        onSubmit={preventNativeSubmit(handleSubmit)}
      >
        <VStack spacing={2} align="stretch">
          <Badge
            alignSelf="flex-start"
            colorScheme="brand"
            px={3}
            py={1}
            rounded="full"
            textTransform="none"
          >
            Student exam result lookup
          </Badge>
          <Text color="gray.600" fontSize="sm">
            Enter the exam number printed for that exam to view the
            student&apos;s result sheet.
          </Text>
        </VStack>

        {/* <SimpleGrid columns={{ base: 1, md: 2 }} spacing={3}>
          <Div
            rounded="xl"
            borderWidth="1px"
            borderColor="brand.100"
            bg="brand.50"
            p={4}
          >
            <HStack align="start" spacing={3}>
              <Div rounded="lg" bg="white" p={2} color="brand.600">
                <Icon as={IdentificationIcon} boxSize={5} />
              </Div>
              <Div>
                <Text fontWeight="semibold" color="gray.800">
                  One-step check
                </Text>
                <Text fontSize="sm" color="gray.600">
                  No password or extra code required.
                </Text>
              </Div>
            </HStack>
          </Div>
          <Div
            rounded="xl"
            borderWidth="1px"
            borderColor="orange.100"
            bg="orange.50"
            p={4}
          >
            <HStack align="start" spacing={3}>
              <Div rounded="lg" bg="white" p={2} color="orange.500">
                <Icon as={TrophyIcon} boxSize={5} />
              </Div>
              <Div>
                <Text fontWeight="semibold" color="gray.800">
                  Instant result sheet
                </Text>
                <Text fontSize="sm" color="gray.600">
                  See score summary, subjects, and performance overview.
                </Text>
              </Div>
            </HStack>
          </Div>
        </SimpleGrid> */}

        <FormControl isInvalid={!!errors.exam_no}>
          <FormLabel>Exam Number</FormLabel>
          <Input
            type="text"
            size="lg"
            value={data.exam_no}
            onChange={(e) => setData('exam_no', e.currentTarget.value)}
            placeholder="Enter exam number"
          />
          <FormErrorMessage>{errors.exam_no}</FormErrorMessage>
        </FormControl>

        <BrandButton
          isLoading={processing}
          type="submit"
          title="Check Result"
          size="lg"
        />

        <HStack justify="space-between" flexWrap="wrap" spacing={3}>
          <Text fontSize="sm" color="gray.500">
            Looking for another access point?
          </Text>
          <HStack spacing={4}>
            <Button
              as={InertiaLink}
              href={route('student-login')}
              colorScheme="brand"
              variant="link"
              size="sm"
            >
              Student Login
            </Button>
            <Button
              as={InertiaLink}
              href={route('student.exam.login.create')}
              colorScheme="brand"
              variant="link"
              size="sm"
            >
              Exam Login
            </Button>
          </HStack>
        </HStack>
      </VStack>
    </CenteredLayout>
  );
}
