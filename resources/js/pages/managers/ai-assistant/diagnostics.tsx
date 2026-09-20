import React from 'react';
import {
  Alert,
  AlertDescription,
  AlertIcon,
  AlertTitle,
  Badge,
  Box,
  Divider,
  Heading,
  HStack,
  SimpleGrid,
  Stack,
  Table,
  TableContainer,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
  useColorModeValue,
} from '@chakra-ui/react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody } from '@/components/slab';
import { PageTitle } from '@/components/page-header';

interface Summary {
  window_days: number;
  runs: number;
  failures: number;
  failure_rate: number;
  grounded_rate: number;
  clarification_rate: number;
  actions_prepared: number;
  actions_rejected: number;
  median_duration_ms: number;
  p95_duration_ms: number;
  input_tokens: number;
  output_tokens: number;
  avg_model_calls: number;
  avg_tool_calls: number;
  avg_retrieval_calls: number;
}

interface Props {
  summary: Summary;
  feedback: { helpful: number; not_helpful: number; rated: number };
  outcomes: Record<string, number>;
  failures: Record<string, number>;
  byRole: { role: string; runs: number; avg_duration_ms: number }[];
  institutions: { institution: string; runs: number; failures: number }[];
  controls: Record<string, string | number | boolean | null>;
}

function humanize(value: string): string {
  return value.replace(/_/g, ' ');
}

function formatMs(value: number): string {
  return value >= 1000 ? `${(value / 1000).toFixed(1)}s` : `${value}ms`;
}

function Stat({
  label,
  value,
  hint,
  tone,
}: {
  label: string;
  value: string;
  hint?: string;
  tone?: string;
}) {
  const border = useColorModeValue('gray.200', 'gray.700');
  const muted = useColorModeValue('gray.600', 'gray.300');

  return (
    <Box borderWidth={1} borderColor={border} rounded="lg" p={4}>
      <Text fontSize="xs" color={muted} textTransform="capitalize">
        {label}
      </Text>
      <Text fontSize="2xl" fontWeight="bold" color={tone} mt={1}>
        {value}
      </Text>
      {hint ? (
        <Text fontSize="xs" color={muted} mt={1}>
          {hint}
        </Text>
      ) : null}
    </Box>
  );
}

export default function AssistantDiagnosticsPage({
  summary,
  feedback,
  outcomes,
  failures,
  byRole,
  institutions,
  controls,
}: Props) {
  const muted = useColorModeValue('gray.600', 'gray.300');
  const border = useColorModeValue('gray.200', 'gray.700');
  const hasRuns = summary.runs > 0;
  const assistantEnabled = Boolean(controls.assistant_enabled);

  return (
    <ManagerDashboardLayout>
      <Stack spacing={5}>
        <Box>
          <PageTitle>AI assistant diagnostics</PageTitle>
          <Text color={muted} fontSize="sm" mt={1}>
            Operational health for the last {summary.window_days} days. These
            figures come from run telemetry only — no message content is stored
            or shown here.
          </Text>
        </Box>

        {!assistantEnabled && (
          <Alert status="warning" rounded="md" alignItems="flex-start">
            <AlertIcon />
            <Box>
              <AlertTitle fontSize="sm">The assistant is disabled</AlertTitle>
              <AlertDescription fontSize="sm">
                Every assistant request is currently refused with a safe
                message. Set AI_ASSISTANT_ENABLED to re-enable it.
              </AlertDescription>
            </Box>
          </Alert>
        )}

        {!hasRuns && (
          <Alert status="info" rounded="md" alignItems="flex-start">
            <AlertIcon />
            <Box>
              <AlertTitle fontSize="sm">
                No assistant activity in this window
              </AlertTitle>
              <AlertDescription fontSize="sm">
                Nothing has been recorded in the last {summary.window_days}{' '}
                days. Figures below will populate once the assistant is used.
              </AlertDescription>
            </Box>
          </Alert>
        )}

        <Slab>
          <SlabBody>
            <Heading size="sm" mb={4}>
              Volume and reliability
            </Heading>
            <SimpleGrid columns={{ base: 1, sm: 2, lg: 4 }} spacing={4}>
              <Stat label="Turns" value={String(summary.runs)} />
              <Stat
                label="Failures"
                value={`${summary.failure_rate}%`}
                hint={`${summary.failures} failed turns`}
                tone={summary.failure_rate > 5 ? 'red.500' : undefined}
              />
              <Stat
                label="Grounded answers"
                value={`${summary.grounded_rate}%`}
                hint="Backed by a verified knowledge source"
              />
              <Stat
                label="Clarifications asked"
                value={`${summary.clarification_rate}%`}
                hint="Turns that asked for more context"
              />
            </SimpleGrid>
          </SlabBody>
        </Slab>

        <Slab>
          <SlabBody>
            <Heading size="sm" mb={4}>
              Latency and cost
            </Heading>
            <SimpleGrid columns={{ base: 1, sm: 2, lg: 4 }} spacing={4}>
              <Stat
                label="Median turn"
                value={formatMs(summary.median_duration_ms)}
              />
              <Stat
                label="95th percentile turn"
                value={formatMs(summary.p95_duration_ms)}
                tone={
                  summary.p95_duration_ms > 15000 ? 'orange.500' : undefined
                }
              />
              <Stat
                label="Input tokens"
                value={summary.input_tokens.toLocaleString()}
              />
              <Stat
                label="Output tokens"
                value={summary.output_tokens.toLocaleString()}
              />
            </SimpleGrid>
            <Divider my={4} />
            <SimpleGrid columns={{ base: 1, sm: 3 }} spacing={4}>
              <Stat
                label="Avg model calls / turn"
                value={String(summary.avg_model_calls)}
              />
              <Stat
                label="Avg tool calls / turn"
                value={String(summary.avg_tool_calls)}
              />
              <Stat
                label="Avg retrieval calls / turn"
                value={String(summary.avg_retrieval_calls)}
              />
            </SimpleGrid>
          </SlabBody>
        </Slab>

        <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={5}>
          <Slab>
            <SlabBody>
              <Heading size="sm" mb={4}>
                User feedback
              </Heading>
              <SimpleGrid columns={{ base: 1, sm: 3 }} spacing={4}>
                <Stat
                  label="Helpful"
                  value={String(feedback.helpful)}
                  tone="green.500"
                />
                <Stat
                  label="Not helpful"
                  value={String(feedback.not_helpful)}
                  tone={feedback.not_helpful > 0 ? 'red.500' : undefined}
                />
                <Stat label="Total rated" value={String(feedback.rated)} />
              </SimpleGrid>
            </SlabBody>
          </Slab>

          <Slab>
            <SlabBody>
              <Heading size="sm" mb={4}>
                Turn outcomes
              </Heading>
              {Object.keys(outcomes).length === 0 ? (
                <Text fontSize="sm" color={muted}>
                  No turns recorded yet.
                </Text>
              ) : (
                <Stack spacing={2}>
                  {Object.entries(outcomes).map(([outcome, total]) => (
                    <HStack key={outcome} justify="space-between">
                      <Text fontSize="sm" textTransform="capitalize">
                        {humanize(outcome)}
                      </Text>
                      <Badge
                        colorScheme={outcome === 'failed' ? 'red' : 'gray'}
                      >
                        {total}
                      </Badge>
                    </HStack>
                  ))}
                </Stack>
              )}
            </SlabBody>
          </Slab>
        </SimpleGrid>

        <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={5}>
          <Slab>
            <SlabBody>
              <Heading size="sm" mb={4}>
                Failure reasons
              </Heading>
              {Object.keys(failures).length === 0 ? (
                <Text fontSize="sm" color={muted}>
                  No failures recorded in this window.
                </Text>
              ) : (
                <Stack spacing={2}>
                  {Object.entries(failures).map(([reason, total]) => (
                    <HStack key={reason} justify="space-between">
                      <Text fontSize="sm" textTransform="capitalize">
                        {humanize(reason ?? 'unknown')}
                      </Text>
                      <Badge colorScheme="red">{total}</Badge>
                    </HStack>
                  ))}
                </Stack>
              )}
            </SlabBody>
          </Slab>

          <Slab>
            <SlabBody>
              <Heading size="sm" mb={4}>
                Usage by role
              </Heading>
              {byRole.length === 0 ? (
                <Text fontSize="sm" color={muted}>
                  No turns recorded yet.
                </Text>
              ) : (
                <TableContainer>
                  <Table size="sm" variant="simple">
                    <Thead>
                      <Tr>
                        <Th>Role</Th>
                        <Th isNumeric>Turns</Th>
                        <Th isNumeric>Avg time</Th>
                      </Tr>
                    </Thead>
                    <Tbody>
                      {byRole.map((row) => (
                        <Tr key={row.role}>
                          <Td textTransform="capitalize">{row.role}</Td>
                          <Td isNumeric>{row.runs}</Td>
                          <Td isNumeric>{formatMs(row.avg_duration_ms)}</Td>
                        </Tr>
                      ))}
                    </Tbody>
                  </Table>
                </TableContainer>
              )}
            </SlabBody>
          </Slab>
        </SimpleGrid>

        <Slab>
          <SlabBody>
            <Heading size="sm" mb={4}>
              Busiest institutions
            </Heading>
            {institutions.length === 0 ? (
              <Text fontSize="sm" color={muted}>
                No institution-scoped assistant activity in this window.
              </Text>
            ) : (
              <TableContainer>
                <Table size="sm" variant="simple">
                  <Thead>
                    <Tr>
                      <Th>Institution</Th>
                      <Th isNumeric>Turns</Th>
                      <Th isNumeric>Failures</Th>
                    </Tr>
                  </Thead>
                  <Tbody>
                    {institutions.map((row) => (
                      <Tr key={row.institution}>
                        <Td>{row.institution}</Td>
                        <Td isNumeric>{row.runs}</Td>
                        <Td isNumeric>{row.failures}</Td>
                      </Tr>
                    ))}
                  </Tbody>
                </Table>
              </TableContainer>
            )}
          </SlabBody>
        </Slab>

        <Slab>
          <SlabBody>
            <Heading size="sm" mb={1}>
              Operational controls
            </Heading>
            <Text fontSize="sm" color={muted} mb={4}>
              Current configuration. Change these through environment variables
              and redeploy; the assistant reads them on every request, so
              disabling it takes effect immediately.
            </Text>
            <SimpleGrid columns={{ base: 1, md: 2 }} spacing={3}>
              {Object.entries(controls).map(([key, value]) => (
                <HStack
                  key={key}
                  justify="space-between"
                  borderWidth={1}
                  borderColor={border}
                  rounded="md"
                  px={3}
                  py={2}
                  gap={4}
                >
                  <Text fontSize="sm" textTransform="capitalize">
                    {humanize(key)}
                  </Text>
                  {typeof value === 'boolean' ? (
                    <Badge colorScheme={value ? 'green' : 'red'}>
                      {value ? 'On' : 'Off'}
                    </Badge>
                  ) : (
                    <Text fontSize="sm" fontWeight="semibold" textAlign="right">
                      {String(value ?? '—')}
                    </Text>
                  )}
                </HStack>
              ))}
            </SimpleGrid>
          </SlabBody>
        </Slab>
      </Stack>
    </ManagerDashboardLayout>
  );
}
