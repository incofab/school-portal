import React from 'react';
import {
  Badge,
  Box,
  Button,
  HStack,
  Select,
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
import { Inertia } from '@inertiajs/inertia';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Classification, Student, Topic } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface TopicWithCounts extends Topic {
  attempted_students_count?: number;
}

interface Props {
  topics: TopicWithCounts[];
  selectedTopic?: Topic;
  classifications: Classification[];
  selectedClassificationId?: number;
  students: Student[];
}

export default function PracticeProgressTeacher({
  topics,
  selectedTopic,
  classifications,
  selectedClassificationId,
  students,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const attemptedCount = students.filter(
    (student) => student.practice_summary?.attempts_count
  ).length;
  const missingCount = students.length - attemptedCount;

  function visit(topicId?: number, classificationId?: number) {
    const data: Record<string, number> = {};
    if (topicId ?? selectedTopic?.id) {
      data.topic_id = topicId ?? selectedTopic!.id;
    }
    if (classificationId ?? selectedClassificationId) {
      data.classification_id = classificationId ?? selectedClassificationId!;
    }

    Inertia.visit(instRoute('courses.practice-progress'), {
      data,
      preserveState: true,
    });
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Topic Practice Progress" />
        <SlabBody>
          <HStack spacing={3} align="end" mb={5}>
            <Box flex={1}>
              <Text fontWeight="semibold" mb={1}>
                Topic
              </Text>
              <Select
                value={selectedTopic?.id ?? ''}
                onChange={(event) =>
                  visit(Number(event.target.value), undefined)
                }
              >
                {topics.map((topic) => (
                  <option key={topic.id} value={topic.id}>
                    {topic.course?.title} - {topic.title}
                  </option>
                ))}
              </Select>
            </Box>
            <Box w={{ base: '100%', md: 260 }}>
              <Text fontWeight="semibold" mb={1}>
                Class
              </Text>
              <Select
                value={selectedClassificationId ?? ''}
                onChange={(event) =>
                  visit(undefined, Number(event.target.value))
                }
              >
                {classifications.map((classification) => (
                  <option key={classification.id} value={classification.id}>
                    {classification.title}
                  </option>
                ))}
              </Select>
            </Box>
            <Button
              colorScheme="brand"
              variant="outline"
              onClick={() => Inertia.visit(instRoute('courses.index'))}
            >
              Subjects
            </Button>
          </HStack>

          <SimpleGrid columns={{ base: 1, md: 4 }} spacing={3} mb={5}>
            <Box borderWidth="1px" borderRadius="8px" p={4}>
              <Text color="gray.500">Students</Text>
              <Text fontSize="2xl" fontWeight="bold">
                {students.length}
              </Text>
            </Box>
            <Box borderWidth="1px" borderRadius="8px" p={4}>
              <Text color="gray.500">Attempted</Text>
              <Text fontSize="2xl" fontWeight="bold">
                {attemptedCount}
              </Text>
            </Box>
            <Box borderWidth="1px" borderRadius="8px" p={4}>
              <Text color="gray.500">Not Attempted</Text>
              <Text fontSize="2xl" fontWeight="bold">
                {missingCount}
              </Text>
            </Box>
            <Box borderWidth="1px" borderRadius="8px" p={4}>
              <Text color="gray.500">Average Best</Text>
              <Text fontSize="2xl" fontWeight="bold">
                {attemptedCount
                  ? Math.round(
                      students.reduce(
                        (total, student) =>
                          total +
                          (student.practice_summary?.best_percentage ?? 0),
                        0
                      ) / attemptedCount
                    )
                  : 0}
                %
              </Text>
            </Box>
          </SimpleGrid>

          <Table size="sm">
            <Thead>
              <Tr>
                <Th>Student</Th>
                <Th>Class</Th>
                <Th>Status</Th>
                <Th isNumeric>Attempts</Th>
                <Th isNumeric>Latest</Th>
                <Th isNumeric>Best</Th>
                <Th>Attempt History</Th>
              </Tr>
            </Thead>
            <Tbody>
              {students.map((student) => {
                const summary = student.practice_summary;
                const attempted = !!summary?.attempts_count;

                return (
                  <Tr key={student.id}>
                    <Td>
                      <VStack align="start" spacing={0}>
                        <Text>
                          {student.user?.full_name ?? student.full_code}
                        </Text>
                        <Text color="gray.500" fontSize="xs">
                          {student.full_code}
                        </Text>
                      </VStack>
                    </Td>
                    <Td>{student.classification?.title}</Td>
                    <Td>
                      <Badge colorScheme={attempted ? 'green' : 'red'}>
                        {attempted ? 'Attempted' : 'Not attempted'}
                      </Badge>
                    </Td>
                    <Td isNumeric>{summary?.attempts_count ?? 0}</Td>
                    <Td isNumeric>
                      {summary
                        ? `${summary.latest_score}/${summary.latest_questions_count} (${summary.latest_percentage}%)`
                        : '-'}
                    </Td>
                    <Td isNumeric>
                      {summary
                        ? `${summary.best_score}/${summary.best_questions_count} (${summary.best_percentage}%)`
                        : '-'}
                    </Td>
                    <Td>
                      {summary?.attempts?.length ? (
                        <VStack align="start" spacing={1}>
                          {summary.attempts.map((attempt) => (
                            <Text key={attempt.id} fontSize="sm">
                              #{attempt.attempt_number}: {attempt.score}/
                              {attempt.questions_count} ({attempt.percentage}%)
                            </Text>
                          ))}
                        </VStack>
                      ) : (
                        <Text color="gray.500">No attempts</Text>
                      )}
                    </Td>
                  </Tr>
                );
              })}
            </Tbody>
          </Table>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
