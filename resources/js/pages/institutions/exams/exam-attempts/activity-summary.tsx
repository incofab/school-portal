import React, { useEffect, useMemo, useState } from 'react';
import {
  Badge,
  Box,
  HStack,
  Progress,
  Spinner,
  Table,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Event } from '@/types/models';
import { useWeb } from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface ActivityAttempt {
  exam_id: number;
  exam_no: string;
  student_name: string;
  student_code?: string | null;
  status: string;
  activity_status: 'active' | 'idle' | 'submitted' | 'not_started';
  started_at?: string | null;
  last_activity_at?: string | null;
  last_ping_at?: string | null;
  answered_questions_count: number;
  total_questions_count: number;
  progress_percentage: number;
  current_question_index?: number | null;
  last_question?: { question_no?: number | null } | null;
  submitted_at?: string | null;
}

interface Summary {
  active_threshold_seconds: number;
  attempts: ActivityAttempt[];
}

interface Props {
  event: Event;
  summary: Summary;
}

export default function ActivitySummary({ event, summary }: Props) {
  const [data, setData] = useState(summary);
  const [loading, setLoading] = useState(false);
  const web = useWeb();
  const { instRoute } = useInstitutionRoute();
  const endpoint = useMemo(
    () => instRoute('events.attempt-activity', [event.id]),
    [event.id]
  );

  useEffect(() => {
    let cancelled = false;

    async function refresh() {
      if (document.hidden) {
        return;
      }

      setLoading(true);
      try {
        const response = await web.get(endpoint, {
          headers: { Accept: 'application/json' },
        });
        if (!cancelled) {
          setData({
            active_threshold_seconds: response.data.active_threshold_seconds,
            attempts: response.data.attempts,
          });
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    const intervalId = window.setInterval(refresh, 20000);

    return () => {
      cancelled = true;
      window.clearInterval(intervalId);
    };
  }, [endpoint]);

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading>
          <HStack justify={'space-between'} align={'start'}>
            <Box>
              <Text as={'div'} size={'md'} fontWeight={'medium'}>
                {event.title}
              </Text>
              <Text as={'div'} size={'sm'}>
                CBT Activity
              </Text>
            </Box>
            {loading && <Spinner size={'sm'} />}
          </HStack>
        </SlabHeading>
        <SlabBody>
          {data.attempts.length === 0 ? (
            <Text>No exam attempts have been created for this event.</Text>
          ) : (
            <Box overflowX={'auto'}>
              <Table size={'sm'}>
                <Thead>
                  <Tr>
                    <Th>Student</Th>
                    <Th>Status</Th>
                    <Th>Progress</Th>
                    <Th>Last Question</Th>
                    <Th>Started</Th>
                    <Th>Last Seen</Th>
                    <Th>Submitted</Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {data.attempts.map((attempt) => (
                    <Tr key={attempt.exam_id}>
                      <Td>
                        <Text fontWeight={'medium'}>
                          {attempt.student_name}
                        </Text>
                        <Text fontSize={'xs'} color={'gray.600'}>
                          {attempt.student_code ?? attempt.exam_no}
                        </Text>
                      </Td>
                      <Td>
                        <Badge
                          colorScheme={statusColor(attempt.activity_status)}
                        >
                          {attempt.activity_status.replace('_', ' ')}
                        </Badge>
                      </Td>
                      <Td minW={'180px'}>
                        <Text fontSize={'sm'}>
                          {attempt.answered_questions_count}/
                          {attempt.total_questions_count} answered
                        </Text>
                        <Progress
                          value={attempt.progress_percentage}
                          size={'sm'}
                          rounded={'sm'}
                        />
                      </Td>
                      <Td>
                        {attempt.last_question?.question_no
                          ? `Q${attempt.last_question.question_no}`
                          : attempt.current_question_index !== null &&
                            attempt.current_question_index !== undefined
                          ? `#${attempt.current_question_index + 1}`
                          : '-'}
                      </Td>
                      <Td>{formatDate(attempt.started_at)}</Td>
                      <Td>
                        {formatDate(
                          attempt.last_ping_at ?? attempt.last_activity_at
                        )}
                      </Td>
                      <Td>{formatDate(attempt.submitted_at)}</Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </Box>
          )}
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function statusColor(status: ActivityAttempt['activity_status']) {
  if (status === 'active') {
    return 'green';
  }
  if (status === 'submitted') {
    return 'blue';
  }
  if (status === 'idle') {
    return 'orange';
  }
  return 'gray';
}

function formatDate(value?: string | null) {
  if (!value) {
    return '-';
  }

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(value));
}
