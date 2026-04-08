import { Div } from '@/components/semantic';
import { Exam, Institution, Student } from '@/types/models';
import route from '@/util/route';
import {
  Badge,
  Box,
  Circle,
  HStack,
  Icon,
  Progress,
  SimpleGrid,
  Stack,
  Text,
  VStack,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  ArrowPathIcon,
  BuildingLibraryIcon,
  ChartBarIcon,
  CheckBadgeIcon,
  DocumentTextIcon,
  RectangleStackIcon,
} from '@heroicons/react/24/outline';
import React from 'react';

interface Props {
  exam: Exam;
  institution: Institution;
}

const getPerformanceRemark = (percent: number) => {
  if (percent >= 85) return 'Outstanding performance';
  if (percent >= 70) return 'Strong performance';
  if (percent >= 50) return 'Fair performance';
  return 'Room for improvement';
};

export default function ExamResultSheet({ exam, institution }: Props) {
  const student = exam.examable as Student;
  const totalScore = Number(exam.score ?? 0);
  const totalQuestions = Number(exam.num_of_questions ?? 0);
  const scorePercent =
    totalQuestions > 0 ? Math.round((totalScore / totalQuestions) * 100) : 0;
  const subjectCount = exam.exam_courseables?.length ?? 0;
  const resultMeta = [
    {
      label: 'Exam Number',
      value: exam.exam_no,
      icon: DocumentTextIcon,
    },
    {
      label: 'Student ID',
      value: student.full_code ?? student.code,
      icon: CheckBadgeIcon,
    },
    {
      label: 'Class',
      value: student.classification?.title ?? 'Not available',
      icon: RectangleStackIcon,
    },
    {
      label: 'Institution',
      value: institution.name,
      icon: BuildingLibraryIcon,
    },
  ];

  return (
    <Div
      minH="100vh"
      py={{ base: 8, md: 12 }}
      px={{ base: 4, md: 6 }}
      bgGradient="linear(to-br, brand.50, orange.50, white)"
      backgroundImage={
        institution.institution_group?.banner
          ? `linear-gradient(rgba(255,255,255,0.96), rgba(255,255,255,0.96)), url(${institution.institution_group.banner})`
          : undefined
      }
      backgroundSize="cover"
      backgroundAttachment="fixed"
    >
      <Box
        maxW="6xl"
        mx="auto"
        rounded={{ base: '2xl', md: '3xl' }}
        overflow="hidden"
        bg="white"
        borderWidth="1px"
        borderColor="whiteAlpha.700"
        shadow="0 20px 60px rgba(15, 23, 42, 0.12)"
      >
        <Box
          px={{ base: 5, md: 10 }}
          py={{ base: 8, md: 10 }}
          bgGradient="linear(to-r, brand.700, brand.500, orange.400)"
          color="white"
        >
          <Stack
            direction={{ base: 'column', lg: 'row' }}
            spacing={6}
            justify="space-between"
            align={{ base: 'stretch', lg: 'center' }}
          >
            <VStack align="start" spacing={3}>
              <Badge
                bg="whiteAlpha.250"
                color="white"
                px={3}
                py={1}
                rounded="full"
                textTransform="none"
              >
                Verified student exam result
              </Badge>
              <VStack align="start" spacing={1}>
                <Text
                  fontSize={{ base: '2xl', md: '4xl' }}
                  fontWeight="bold"
                  lineHeight="1.1"
                >
                  {student.user?.full_name ?? 'Student Result'}
                </Text>
                <Text
                  fontSize={{ base: 'md', md: 'lg' }}
                  color="whiteAlpha.900"
                >
                  {exam.event?.title}
                </Text>
              </VStack>
              <Text maxW="2xl" color="whiteAlpha.900">
                View the student&apos;s exam performance summary and
                subject-by-subject score breakdown for this event.
              </Text>
            </VStack>

            <Box
              rounded="2xl"
              bg="whiteAlpha.200"
              borderWidth="1px"
              borderColor="whiteAlpha.300"
              px={{ base: 5, md: 6 }}
              py={{ base: 4, md: 5 }}
              backdropFilter="blur(10px)"
            >
              <VStack spacing={3}>
                <Circle
                  size={{ base: '100px', md: '120px' }}
                  bg="white"
                  color="brand.700"
                >
                  <VStack spacing={0}>
                    <Text
                      fontSize={{ base: '2xl', md: '3xl' }}
                      fontWeight="extrabold"
                    >
                      {scorePercent}%
                    </Text>
                    <Text fontSize="xs" color="gray.500">
                      overall score
                    </Text>
                  </VStack>
                </Circle>
                <VStack spacing={0}>
                  <Text fontSize="2xl" fontWeight="bold">
                    {totalScore}/{totalQuestions}
                  </Text>
                  <Text color="whiteAlpha.900" fontSize="sm">
                    {getPerformanceRemark(scorePercent)}
                  </Text>
                </VStack>
              </VStack>
            </Box>
          </Stack>
        </Box>

        <Box px={{ base: 5, md: 10 }} py={{ base: 6, md: 8 }}>
          <SimpleGrid columns={{ base: 1, md: 2, xl: 4 }} spacing={4}>
            {resultMeta.map((item) => (
              <Box
                key={item.label}
                rounded="2xl"
                borderWidth="1px"
                borderColor="gray.100"
                bg="gray.50"
                p={4}
              >
                <HStack spacing={3} align="start">
                  <Circle size="42px" bg="white" color="brand.600" shadow="sm">
                    <Icon as={item.icon} boxSize={5} />
                  </Circle>
                  <VStack align="start" spacing={0.5}>
                    <Text
                      fontSize="xs"
                      textTransform="uppercase"
                      letterSpacing="widest"
                      color="gray.500"
                    >
                      {item.label}
                    </Text>
                    <Text fontWeight="semibold" color="gray.800">
                      {item.value}
                    </Text>
                  </VStack>
                </HStack>
              </Box>
            ))}
          </SimpleGrid>

          {/* <SimpleGrid columns={{ base: 1, lg: 3 }} spacing={5} mt={6}> */}
          <HStack rounded="2xl" borderWidth="1px" borderColor="gray.100" p={5}>
            <HStack justify="space-between" mb={4}>
              <Div>
                <HStack spacing={2}>
                  <Icon as={ChartBarIcon} color="brand.600" boxSize={5} />
                  <Text fontWeight="bold" color="gray.800">
                    Performance Summary
                  </Text>
                </HStack>
                <Badge colorScheme="green" textTransform="none">
                  {getPerformanceRemark(scorePercent)}
                </Badge>
                <Box>
                  <HStack justify="space-between" mb={2}>
                    <Text fontSize="sm" color="gray.600">
                      Score progress
                    </Text>
                    <Text fontWeight="semibold" color="gray.800">
                      {scorePercent}%
                    </Text>
                  </HStack>
                  <Progress
                    value={scorePercent}
                    colorScheme="brand"
                    rounded="full"
                    h="10px"
                  />
                </Box>
              </Div>
            </HStack>
            <VStack align="stretch" spacing={4}>
              <SimpleGrid columns={2} spacing={3}>
                <Box rounded="xl" bg="brand.50" p={4}>
                  <Text
                    fontSize="xs"
                    textTransform="uppercase"
                    color="gray.500"
                  >
                    Subjects
                  </Text>
                  <Text fontSize="2xl" fontWeight="bold" color="brand.700">
                    {subjectCount}
                  </Text>
                </Box>
                <Box rounded="xl" bg="orange.50" p={4}>
                  <Text
                    fontSize="xs"
                    textTransform="uppercase"
                    color="gray.500"
                  >
                    Questions
                  </Text>
                  <Text fontSize="2xl" fontWeight="bold" color="orange.500">
                    {totalQuestions}
                  </Text>
                </Box>
              </SimpleGrid>
            </VStack>
          </HStack>

          {/* <Box rounded="2xl" borderWidth="1px" borderColor="gray.100" p={5}>
              <VStack align="start" spacing={3}>
                <Badge colorScheme="purple" textTransform="none">
                  Event snapshot
                </Badge>
                <Text fontWeight="bold" color="gray.800">
                  {exam.event?.title}
                </Text>
                <Text fontSize="sm" color="gray.600">
                  Result sheet generated from the submitted exam record linked
                  to this exam number.
                </Text>
                <Text fontSize="sm" color="gray.600">
                  Each subject card below shows the number of questions answered
                  and the final score recorded for that subject.
                </Text>
              </VStack>
            </Box> */}

          {/* <Box rounded="2xl" borderWidth="1px" borderColor="gray.100" p={5}>
              <VStack align="start" spacing={3}>
                <Badge colorScheme="blue" textTransform="none">
                  Quick actions
                </Badge>
                <Box
                  as={InertiaLink}
                  href={route('exam-results.create')}
                  rounded="xl"
                  borderWidth="1px"
                  borderColor="gray.100"
                  p={4}
                  w="full"
                  _hover={{ borderColor: 'brand.200', bg: 'brand.50' }}
                >
                  <HStack justify="space-between">
                    <VStack align="start" spacing={0}>
                      <Text fontWeight="semibold" color="gray.800">
                        Check another result
                      </Text>
                      <Text fontSize="sm" color="gray.500">
                        Enter a different exam number
                      </Text>
                    </VStack>
                    <Icon as={ArrowPathIcon} boxSize={5} color="brand.600" />
                  </HStack>
                </Box>
                <Box
                  as={InertiaLink}
                  href={route('login')}
                  rounded="xl"
                  borderWidth="1px"
                  borderColor="gray.100"
                  p={4}
                  w="full"
                  _hover={{ borderColor: 'orange.200', bg: 'orange.50' }}
                >
                  <HStack justify="space-between">
                    <VStack align="start" spacing={0}>
                      <Text fontWeight="semibold" color="gray.800">
                        Go to main login
                      </Text>
                      <Text fontSize="sm" color="gray.500">
                        Return to other portal access options
                      </Text>
                    </VStack>
                    <Icon
                      as={BuildingLibraryIcon}
                      boxSize={5}
                      color="orange.500"
                    />
                  </HStack>
                </Box>
              </VStack>
            </Box> */}
          {/* </SimpleGrid> */}

          <Box mt={6}>
            <HStack
              justify="space-between"
              mb={4}
              align={{ base: 'start', md: 'center' }}
              flexDir={{ base: 'column', md: 'row' }}
            >
              <VStack align="start" spacing={1}>
                <Text
                  fontSize={{ base: 'xl', md: '2xl' }}
                  fontWeight="bold"
                  color="gray.800"
                >
                  Subject Breakdown
                </Text>
                <Text color="gray.600" fontSize="sm">
                  A clean view of how the student performed in each subject for
                  this event.
                </Text>
              </VStack>
              <Badge
                colorScheme="brand"
                textTransform="none"
                px={3}
                py={1}
                rounded="full"
              >
                {subjectCount} subject{subjectCount === 1 ? '' : 's'}
              </Badge>
            </HStack>

            <SimpleGrid columns={{ base: 1, xl: 2 }} spacing={4}>
              {exam.exam_courseables?.map((examCourseable, index) => {
                const numOfQuestions = Number(
                  examCourseable.num_of_questions ?? 0
                );
                const subjectScore = Number(examCourseable.score ?? 0);
                const subjectPercent =
                  numOfQuestions > 0
                    ? Math.round((subjectScore / numOfQuestions) * 100)
                    : 0;

                return (
                  <Box
                    key={examCourseable.id}
                    rounded="2xl"
                    borderWidth="1px"
                    borderColor="gray.100"
                    p={5}
                    bg="white"
                    shadow="sm"
                  >
                    <HStack justify="space-between" align="start" spacing={4}>
                      <HStack spacing={4} align="start">
                        <Circle
                          size="42px"
                          bg="brand.50"
                          color="brand.600"
                          fontWeight="bold"
                        >
                          {index + 1}
                        </Circle>
                        <VStack align="start" spacing={1}>
                          <Text fontWeight="bold" color="gray.800">
                            {examCourseable.courseable?.course?.title ??
                              'Subject'}
                          </Text>
                          <Text fontSize="sm" color="gray.500">
                            {numOfQuestions} question
                            {numOfQuestions === 1 ? '' : 's'}
                          </Text>
                        </VStack>
                      </HStack>
                      <Badge
                        colorScheme={subjectPercent >= 50 ? 'green' : 'red'}
                        textTransform="none"
                      >
                        {subjectPercent}%
                      </Badge>
                    </HStack>
                    <Box mt={4}>
                      <HStack justify="space-between" mb={2}>
                        <Text fontSize="sm" color="gray.600">
                          Subject score
                        </Text>
                        <Text fontWeight="semibold" color="gray.800">
                          {subjectScore}/{numOfQuestions}
                        </Text>
                      </HStack>
                      <Progress
                        value={subjectPercent}
                        colorScheme={subjectPercent >= 50 ? 'green' : 'red'}
                        rounded="full"
                        h="8px"
                      />
                    </Box>
                  </Box>
                );
              })}
            </SimpleGrid>
          </Box>
        </Box>
      </Box>
    </Div>
  );
}
