import React from 'react';
import {
  Badge,
  Box,
  Button,
  HStack,
  SimpleGrid,
  Table,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Course, TopicPracticeAttempt } from '@/types/models';
import PracticeQuestionModal from '@/components/modals/practice-question-modal';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import { Inertia } from '@inertiajs/inertia';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { formatAsDate } from '@/util/util';

interface Props {
  courses: Course[];
  attempts: TopicPracticeAttempt[];
}

function getScoreColor(percentage: number, attempted: boolean) {
  if (!attempted) {
    return 'gray';
  }

  return percentage >= 60 ? 'green' : 'red';
}

export default function PracticeProgressStudent({ courses, attempts }: Props) {
  const practiceQuestionModalToggle = useModalValueToggle<Course>();
  const { instRoute } = useInstitutionRoute();

  const topicCount = courses.reduce(
    (total, course) => total + (course.topics?.length ?? 0),
    0
  );
  const practicedTopicCount = courses.reduce(
    (total, course) =>
      total +
      (course.topics?.filter((topic) => topic.practice_summary?.attempts_count)
        .length ?? 0),
    0
  );

  function openPractice(course: Course) {
    practiceQuestionModalToggle.open(course);
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Topic Practice"
          rightElement={
            <Button
              colorScheme="brand"
              variant="outline"
              onClick={() => Inertia.visit(instRoute('courses.index'))}
            >
              Subjects
            </Button>
          }
        />
        <SlabBody>
          <SimpleGrid columns={{ base: 1, md: 3 }} spacing={3} mb={5}>
            <Box borderWidth="1px" borderRadius="8px" p={4}>
              <Text color="gray.500">Topics to Practice</Text>
              <Text fontSize="2xl" fontWeight="bold">
                {topicCount}
              </Text>
            </Box>
            <Box borderWidth="1px" borderRadius="8px" p={4}>
              <Text color="gray.500">Practiced Topics</Text>
              <Text fontSize="2xl" fontWeight="bold">
                {practicedTopicCount}
              </Text>
            </Box>
            <Box borderWidth="1px" borderRadius="8px" p={4}>
              <Text color="gray.500">Not Attempted</Text>
              <Text fontSize="2xl" fontWeight="bold">
                {topicCount - practicedTopicCount}
              </Text>
            </Box>
          </SimpleGrid>

          <VStack align="stretch" spacing={5}>
            {courses.map((course) => (
              <Box key={course.id} borderWidth="1px" borderRadius="8px" p={4}>
                <HStack justify="space-between" align="start" mb={3}>
                  <Box>
                    <Text fontWeight="bold" fontSize="lg">
                      {course.title}
                    </Text>
                    <Text color="gray.500" fontSize="sm">
                      {course.topics?.length ?? 0} topics available
                    </Text>
                  </Box>
                  <Button
                    size="sm"
                    colorScheme="brand"
                    onClick={() => openPractice(course)}
                  >
                    Practice
                  </Button>
                </HStack>

                <Table size="sm">
                  <Thead>
                    <Tr>
                      <Th>Topic</Th>
                      <Th>Status</Th>
                      <Th isNumeric>Percent Score</Th>
                      <Th isNumeric>Attempts</Th>
                      <Th isNumeric>Best</Th>
                      <Th isNumeric>Latest</Th>
                    </Tr>
                  </Thead>
                  <Tbody>
                    {course.topics?.map((topic) => {
                      const summary = topic.practice_summary;
                      const practiced = !!summary?.attempts_count;
                      const bestPercentage = summary?.best_percentage ?? 0;
                      const latestPercentage = summary?.latest_percentage ?? 0;
                      const scoreColor = getScoreColor(
                        bestPercentage,
                        practiced
                      );

                      return (
                        <Tr key={topic.id}>
                          <Td>{topic.title}</Td>
                          <Td>
                            <Badge colorScheme={practiced ? 'green' : 'gray'}>
                              {practiced ? 'Practiced' : 'Not attempted'}
                            </Badge>
                          </Td>
                          <Td isNumeric>
                            <Badge colorScheme={scoreColor}>
                              {practiced ? `${bestPercentage}%` : 'No score'}
                            </Badge>
                          </Td>
                          <Td isNumeric>{summary?.attempts_count ?? 0}</Td>
                          <Td isNumeric>
                            <Text
                              as="span"
                              color={
                                practiced ? `${scoreColor}.600` : 'gray.500'
                              }
                              fontWeight="semibold"
                            >
                              {bestPercentage}%
                            </Text>
                          </Td>
                          <Td isNumeric>
                            <Text
                              as="span"
                              color={
                                practiced
                                  ? `${getScoreColor(
                                      latestPercentage,
                                      practiced
                                    )}.600`
                                  : 'gray.500'
                              }
                              fontWeight="semibold"
                            >
                              {latestPercentage}%
                            </Text>
                          </Td>
                        </Tr>
                      );
                    })}
                  </Tbody>
                </Table>
              </Box>
            ))}
          </VStack>

          {attempts.length > 0 && (
            <Box mt={6} borderWidth="1px" borderRadius="8px" p={4}>
              <Text fontWeight="bold" mb={3}>
                Recent Attempts
              </Text>
              <Table size="sm">
                <Thead>
                  <Tr>
                    <Th>Course</Th>
                    <Th>Topic</Th>
                    <Th isNumeric>Attempt</Th>
                    <Th isNumeric>Score</Th>
                    <Th>Submitted</Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {attempts.map((attempt) => (
                    <Tr key={attempt.id}>
                      <Td>{attempt.course?.title}</Td>
                      <Td>{attempt.topic?.title}</Td>
                      <Td isNumeric>{attempt.attempt_number}</Td>
                      <Td isNumeric>
                        {attempt.score}/{attempt.questions_count} (
                        {attempt.percentage}%)
                      </Td>
                      <Td>
                        {attempt.submitted_at
                          ? formatAsDate(attempt.submitted_at)
                          : 'Not submitted'}
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </Box>
          )}
        </SlabBody>
      </Slab>

      {practiceQuestionModalToggle.state && (
        <PracticeQuestionModal
          {...practiceQuestionModalToggle.props}
          course={practiceQuestionModalToggle.state}
          onSuccess={() =>
            Inertia.visit(instRoute('courses.view-practice-questions'))
          }
        />
      )}
    </DashboardLayout>
  );
}
