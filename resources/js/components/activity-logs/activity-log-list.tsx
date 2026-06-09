import React, { useMemo, useState } from 'react';
import {
  Badge,
  Box,
  Button,
  Code,
  Divider,
  Drawer,
  DrawerBody,
  DrawerCloseButton,
  DrawerContent,
  DrawerHeader,
  DrawerOverlay,
  FormControl,
  FormLabel,
  Grid,
  HStack,
  Icon,
  IconButton,
  Input,
  Select,
  SimpleGrid,
  Switch,
  Stack,
  Text,
} from '@chakra-ui/react';
import { ArrowDownTrayIcon, EyeIcon } from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { ActivityLog, Institution, InstitutionGroup } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { dateTimeFormat, formatAsDate } from '@/util/util';

interface Props {
  activityLogs: PaginationResponse<ActivityLog>;
  filterOptions: {
    categories: string[];
    severities: string[];
    retentionCategories: string[];
  };
  institutions?: Institution[];
  institutionGroups?: InstitutionGroup[];
  showInstitutionFilter?: boolean;
  showInstitutionGroupFilter?: boolean;
  canExport?: boolean;
  exportUrl?: string;
}

type Filters = {
  category: string;
  event: string;
  actor: string;
  actor_role: string;
  subject: string;
  subject_type: string;
  subject_search: string;
  severity: string;
  institution_id: string;
  institution_group_id: string;
  ip_address: string;
  request_id: string;
  impersonated_only: string;
  retention_category: string;
  'created_at[date_from]': string;
  'created_at[date_to]': string;
};

export default function ActivityLogList({
  activityLogs,
  filterOptions,
  institutions = [],
  institutionGroups = [],
  showInstitutionFilter = false,
  showInstitutionGroupFilter = false,
  canExport = false,
  exportUrl,
}: Props) {
  const [selected, setSelected] = useState<ActivityLog | null>(null);
  const [filters, setFilters] = useState<Filters>(() => currentFilters());

  function currentFilters(): Filters {
    const params = new URL(window.location.href).searchParams;

    return {
      category: params.get('category') ?? '',
      event: params.get('event') ?? '',
      actor: params.get('actor') ?? '',
      actor_role: params.get('actor_role') ?? '',
      subject: params.get('subject') ?? '',
      subject_type: params.get('subject_type') ?? '',
      subject_search: params.get('subject_search') ?? '',
      severity: params.get('severity') ?? '',
      institution_id: params.get('institution_id') ?? '',
      institution_group_id: params.get('institution_group_id') ?? '',
      ip_address: params.get('ip_address') ?? '',
      request_id: params.get('request_id') ?? '',
      impersonated_only: params.get('impersonated_only') ?? '',
      retention_category: params.get('retention_category') ?? '',
      'created_at[date_from]': params.get('created_at[date_from]') ?? '',
      'created_at[date_to]': params.get('created_at[date_to]') ?? '',
    };
  }

  function updateFilter(key: keyof Filters, value: string) {
    setFilters((existing) => ({ ...existing, [key]: value }));
  }

  function applyFilters() {
    const url = new URL(window.location.href);
    Object.entries(filters).forEach(([key, value]) => {
      if (value) {
        url.searchParams.set(key, value);
      } else {
        url.searchParams.delete(key);
      }
    });
    url.searchParams.delete('page');
    Inertia.visit(url.toString(), { preserveState: true });
  }

  function clearFilters() {
    const url = new URL(window.location.href);
    Object.keys(filters).forEach((key) => url.searchParams.delete(key));
    url.searchParams.delete('page');
    Inertia.visit(url.toString(), { preserveState: true });
  }

  function exportLogs() {
    if (!exportUrl) {
      return;
    }

    const url = new URL(exportUrl, window.location.origin);
    Object.entries(filters).forEach(([key, value]) => {
      if (value) {
        url.searchParams.set(key, value);
      }
    });
    window.location.href = url.toString();
  }

  const headers: ServerPaginatedTableHeader<ActivityLog>[] = useMemo(
    () => [
      {
        label: 'Time',
        sortKey: 'createdAt',
        render: (row) => formatAsDate(row.created_at, dateTimeFormat),
      },
      {
        label: 'Category',
        sortKey: 'category',
        render: (row) => <Text whiteSpace="nowrap">{row.category}</Text>,
      },
      {
        label: 'Event',
        value: 'event',
        sortKey: 'event',
      },
      {
        label: 'Action',
        value: 'action',
        sortKey: 'action',
      },
      {
        label: 'Actor',
        render: (row) => row.actor_name ?? 'System',
      },
      {
        label: 'Subject',
        render: (row) => row.subject_name ?? 'N/A',
      },
      ...(showInstitutionFilter
        ? [
            {
              label: 'Institution',
              render: (row: ActivityLog) => row.institution?.name ?? 'Global',
            },
          ]
        : []),
      {
        label: 'Severity',
        sortKey: 'severity',
        render: (row) => <SeverityBadge severity={row.severity} />,
      },
      {
        label: 'Action',
        render: (row) => (
          <IconButton
            aria-label="View activity log"
            icon={<Icon as={EyeIcon} />}
            variant="ghost"
            colorScheme="brand"
            onClick={() => setSelected(row)}
          />
        ),
      },
    ],
    [showInstitutionFilter]
  );

  return (
    <Stack spacing={4}>
      <Box borderWidth={1} borderRadius={8} p={4}>
        <SimpleGrid columns={{ base: 1, md: 3, xl: 4 }} spacing={3}>
          <FormControl>
            <FormLabel>From</FormLabel>
            <Input
              type="date"
              value={filters['created_at[date_from]']}
              onChange={(e) =>
                updateFilter('created_at[date_from]', e.target.value)
              }
            />
          </FormControl>
          <FormControl>
            <FormLabel>To</FormLabel>
            <Input
              type="date"
              value={filters['created_at[date_to]']}
              onChange={(e) =>
                updateFilter('created_at[date_to]', e.target.value)
              }
            />
          </FormControl>
          <FormControl>
            <FormLabel>Category</FormLabel>
            <Select
              value={filters.category}
              onChange={(e) => updateFilter('category', e.target.value)}
            >
              <option value="">All</option>
              {filterOptions.categories.map((category) => (
                <option key={category} value={category}>
                  {category}
                </option>
              ))}
            </Select>
          </FormControl>
          <FormControl>
            <FormLabel>Retention</FormLabel>
            <Select
              value={filters.retention_category}
              onChange={(e) =>
                updateFilter('retention_category', e.target.value)
              }
            >
              <option value="">All</option>
              {filterOptions.retentionCategories.map((category) => (
                <option key={category} value={category}>
                  {category}
                </option>
              ))}
            </Select>
          </FormControl>
          <FormControl>
            <FormLabel>Severity</FormLabel>
            <Select
              value={filters.severity}
              onChange={(e) => updateFilter('severity', e.target.value)}
            >
              <option value="">All</option>
              {filterOptions.severities.map((severity) => (
                <option key={severity} value={severity}>
                  {severity}
                </option>
              ))}
            </Select>
          </FormControl>
          <FormControl>
            <FormLabel>Event</FormLabel>
            <Input
              value={filters.event}
              onChange={(e) => updateFilter('event', e.target.value)}
            />
          </FormControl>
          <FormControl>
            <FormLabel>Actor</FormLabel>
            <Input
              value={filters.actor}
              onChange={(e) => updateFilter('actor', e.target.value)}
            />
          </FormControl>
          <FormControl>
            <FormLabel>Actor Role</FormLabel>
            <Input
              value={filters.actor_role}
              onChange={(e) => updateFilter('actor_role', e.target.value)}
            />
          </FormControl>
          <FormControl>
            <FormLabel>Subject Name</FormLabel>
            <Input
              value={filters.subject}
              onChange={(e) => updateFilter('subject', e.target.value)}
            />
          </FormControl>
          <FormControl>
            <FormLabel>Subject Type</FormLabel>
            <Input
              value={filters.subject_type}
              onChange={(e) => updateFilter('subject_type', e.target.value)}
            />
          </FormControl>
          <FormControl>
            <FormLabel>Subject Search</FormLabel>
            <Input
              value={filters.subject_search}
              onChange={(e) => updateFilter('subject_search', e.target.value)}
            />
          </FormControl>
          <FormControl>
            <FormLabel>IP Address</FormLabel>
            <Input
              value={filters.ip_address}
              onChange={(e) => updateFilter('ip_address', e.target.value)}
            />
          </FormControl>
          <FormControl>
            <FormLabel>Request ID</FormLabel>
            <Input
              value={filters.request_id}
              onChange={(e) => updateFilter('request_id', e.target.value)}
            />
          </FormControl>
          {showInstitutionFilter && (
            <FormControl>
              <FormLabel>Institution</FormLabel>
              <Select
                value={filters.institution_id}
                onChange={(e) => updateFilter('institution_id', e.target.value)}
              >
                <option value="">All</option>
                {institutions.map((institution) => (
                  <option key={institution.id} value={institution.id}>
                    {institution.name}
                  </option>
                ))}
              </Select>
            </FormControl>
          )}
          {showInstitutionGroupFilter && (
            <FormControl>
              <FormLabel>Institution Group</FormLabel>
              <Select
                value={filters.institution_group_id}
                onChange={(e) =>
                  updateFilter('institution_group_id', e.target.value)
                }
              >
                <option value="">All</option>
                {institutionGroups.map((institutionGroup) => (
                  <option key={institutionGroup.id} value={institutionGroup.id}>
                    {institutionGroup.name}
                  </option>
                ))}
              </Select>
            </FormControl>
          )}
          <FormControl display="flex" alignItems="center" gap={3} pt={7}>
            <Switch
              isChecked={filters.impersonated_only === '1'}
              onChange={(e) =>
                updateFilter('impersonated_only', e.target.checked ? '1' : '')
              }
            />
            <FormLabel m={0}>Impersonated only</FormLabel>
          </FormControl>
        </SimpleGrid>
        <HStack mt={4} flexWrap="wrap">
          <Button colorScheme="brand" onClick={applyFilters}>
            Filter
          </Button>
          <Button variant="outline" onClick={clearFilters}>
            Clear
          </Button>
          {canExport && (
            <Button
              leftIcon={<Icon as={ArrowDownTrayIcon} />}
              variant="outline"
              onClick={exportLogs}
            >
              Export CSV
            </Button>
          )}
        </HStack>
      </Box>
      <ServerPaginatedTable
        scroll={true}
        headers={headers}
        data={activityLogs.data}
        keyExtractor={(row) => row.id}
        paginator={activityLogs}
      />
      <ActivityLogDrawer
        activityLog={selected}
        onClose={() => setSelected(null)}
      />
    </Stack>
  );
}

function ActivityLogDrawer({
  activityLog,
  onClose,
}: {
  activityLog: ActivityLog | null;
  onClose(): void;
}) {
  return (
    <Drawer
      isOpen={Boolean(activityLog)}
      placement="right"
      size="lg"
      onClose={onClose}
    >
      <DrawerOverlay />
      <DrawerContent>
        <DrawerCloseButton />
        <DrawerHeader>Activity Detail</DrawerHeader>
        <DrawerBody>
          {activityLog && (
            <Stack spacing={4}>
              <Grid templateColumns="140px 1fr" gap={2}>
                <Text fontWeight="semibold">Actor</Text>
                <Text>{activityLog.actor_name ?? 'System'}</Text>
                <Text fontWeight="semibold">Actor Role</Text>
                <Text>{activityLog.actor_role ?? 'N/A'}</Text>
                <Text fontWeight="semibold">Actor Type</Text>
                <Text wordBreak="break-all">
                  {formatClassName(activityLog.actor_type)}
                </Text>
                <Text fontWeight="semibold">Subject</Text>
                <Text>{activityLog.subject_name ?? 'N/A'}</Text>
                <Text fontWeight="semibold">Subject Type</Text>
                <Text wordBreak="break-all">
                  {formatClassName(activityLog.subject_type)}
                </Text>
                <Text fontWeight="semibold">Institution</Text>
                <Text>{activityLog.institution?.name ?? 'Global'}</Text>
                <Text fontWeight="semibold">Institution Group</Text>
                <Text>{activityLog.institution_group?.name ?? 'N/A'}</Text>
                <Text fontWeight="semibold">Category</Text>
                <Text>{activityLog.category}</Text>
                <Text fontWeight="semibold">Event</Text>
                <Text>{activityLog.event}</Text>
                <Text fontWeight="semibold">Severity</Text>
                <Text>
                  <SeverityBadge severity={activityLog.severity} />
                </Text>
                <Text fontWeight="semibold">Retention</Text>
                <Text>{activityLog.retention_category ?? 'normal'}</Text>
                <Text fontWeight="semibold">Description</Text>
                <Text>{activityLog.description ?? 'N/A'}</Text>
                <Text fontWeight="semibold">Request</Text>
                <Text>
                  {activityLog.method ?? 'N/A'} {activityLog.route_name ?? ''}
                </Text>
                <Text fontWeight="semibold">IP</Text>
                <Text>{activityLog.ip_address ?? 'N/A'}</Text>
                <Text fontWeight="semibold">Request ID</Text>
                <Text>{activityLog.request_id ?? 'N/A'}</Text>
                <Text fontWeight="semibold">URL</Text>
                <Text wordBreak="break-all">{activityLog.url ?? 'N/A'}</Text>
                <Text fontWeight="semibold">User Agent</Text>
                <Text wordBreak="break-all">
                  {activityLog.user_agent ?? 'N/A'}
                </Text>
                <Text fontWeight="semibold">Impersonator</Text>
                <Text>
                  {activityLog.impersonator_id
                    ? `${formatClassName(activityLog.impersonator_type)} #${
                        activityLog.impersonator_id
                      }`
                    : 'N/A'}
                </Text>
                <Text fontWeight="semibold">Integrity Hash</Text>
                <Text wordBreak="break-all">
                  {activityLog.row_hash ?? 'N/A'}
                </Text>
              </Grid>
              {isFinancialLog(activityLog) && (
                <FinancialSummary activityLog={activityLog} />
              )}
              <ValueDiff
                oldValues={activityLog.old_values}
                newValues={activityLog.new_values}
              />
              <Divider />
              <JsonBlock title="Properties" value={activityLog.properties} />
              <JsonBlock title="Old Values" value={activityLog.old_values} />
              <JsonBlock title="New Values" value={activityLog.new_values} />
            </Stack>
          )}
        </DrawerBody>
      </DrawerContent>
    </Drawer>
  );
}

function SeverityBadge({ severity }: { severity: string }) {
  return (
    <HStack spacing={2}>
      <Box
        boxSize={2}
        borderRadius="full"
        bg={`${severityColor(severity)}.500`}
      />
      <Badge colorScheme={severityColor(severity)}>{severity}</Badge>
    </HStack>
  );
}

function ValueDiff({
  oldValues,
  newValues,
}: {
  oldValues?: { [key: string]: any };
  newValues?: { [key: string]: any };
}) {
  const keys = Array.from(
    new Set([...Object.keys(oldValues ?? {}), ...Object.keys(newValues ?? {})])
  );

  if (!keys.length) {
    return null;
  }

  return (
    <Box borderWidth={1} borderRadius={8} p={3}>
      <Text fontWeight="semibold" mb={3}>
        Value Diff
      </Text>
      <Stack spacing={3}>
        {keys.map((key) => (
          <SimpleGrid key={key} columns={{ base: 1, md: 3 }} spacing={2}>
            <Text fontWeight="medium">{key}</Text>
            <Box>
              <Text color="gray.500" fontSize="sm">
                Old
              </Text>
              <Code whiteSpace="pre-wrap" w="100%" p={2}>
                {formatValue(oldValues?.[key])}
              </Code>
            </Box>
            <Box>
              <Text color="gray.500" fontSize="sm">
                New
              </Text>
              <Code whiteSpace="pre-wrap" w="100%" p={2}>
                {formatValue(newValues?.[key])}
              </Code>
            </Box>
          </SimpleGrid>
        ))}
      </Stack>
    </Box>
  );
}

function formatValue(value: unknown) {
  if (value === undefined || value === null) {
    return 'N/A';
  }

  if (typeof value === 'object') {
    return JSON.stringify(value, null, 2);
  }

  return String(value);
}

function formatClassName(value?: string) {
  return value?.split('\\').pop() ?? 'N/A';
}

function isFinancialLog(activityLog: ActivityLog) {
  return [
    'fee',
    'payment',
    'wallet',
    'payroll',
    'expense',
    'integration',
    'notification',
  ].includes(activityLog.category);
}

function FinancialSummary({ activityLog }: { activityLog: ActivityLog }) {
  const props = activityLog.properties ?? {};
  const bankAccount = props.bank_account ?? props.metadata?.bank_account;
  const approvalActor = props.approval_actor;
  const fee = props.fee;
  const metadata = props.metadata ?? {};
  const values: Array<[string, unknown]> = [
    ['Amount', props.amount ?? metadata.amount ?? metadata.amount_paid],
    ['Currency', props.currency ?? metadata.currency],
    ['Reference', props.reference ?? metadata.reference],
    [
      'Transaction',
      props.transaction_reference ??
        metadata.transaction_reference ??
        metadata.transaction_id,
    ],
    ['Provider', props.payment_provider ?? props.provider],
    ['Status', props.status ?? metadata.status ?? metadata.payment_status],
    ['Method', props.payment_method],
    ['Purpose', props.purpose],
    ['Bank', bankAccount?.bank_name ?? metadata.receiver_bank],
    [
      'Account Last 4',
      bankAccount?.account_number_last4 ??
        metadata.receiver_account_last4 ??
        metadata.destination_account_last4,
    ],
    ['Payer', props.payer?.name],
    ['Payee', props.payee?.name],
    ['Approval Actor', approvalActor?.name],
    ['Fee', fee?.title ?? props.title],
  ].filter(
    ([, value]) => value !== undefined && value !== null && value !== ''
  );

  if (!values.length) {
    return null;
  }

  return (
    <Box borderWidth={1} borderRadius={8} p={3}>
      <Text fontWeight="semibold" mb={2}>
        Financial Summary
      </Text>
      <SimpleGrid columns={{ base: 1, md: 2 }} spacing={2}>
        {values.map(([label, value]) => (
          <Box key={label}>
            <Text color="gray.500" fontSize="sm">
              {label}
            </Text>
            <Text wordBreak="break-word">{String(value)}</Text>
          </Box>
        ))}
      </SimpleGrid>
    </Box>
  );
}

function severityColor(severity: string) {
  switch (severity) {
    case 'critical':
      return 'red';
    case 'warning':
      return 'orange';
    case 'security':
      return 'purple';
    case 'error':
      return 'pink';
    case 'notice':
      return 'blue';
    default:
      return 'gray';
  }
}

function JsonBlock({ title, value }: { title: string; value?: any }) {
  return (
    <Box>
      <Text fontWeight="semibold" mb={2}>
        {title}
      </Text>
      <Code display="block" whiteSpace="pre-wrap" p={3} w="100%">
        {JSON.stringify(value ?? {}, null, 2)}
      </Code>
    </Box>
  );
}
