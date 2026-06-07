import React, { useMemo, useState } from 'react';
import {
  Badge,
  Box,
  Button,
  Code,
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
  Stack,
  Text,
} from '@chakra-ui/react';
import { EyeIcon } from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { ActivityLog, Institution } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { dateTimeFormat, formatAsDate } from '@/util/util';

interface Props {
  activityLogs: PaginationResponse<ActivityLog>;
  filterOptions: {
    categories: string[];
    severities: string[];
  };
  institutions?: Institution[];
  showInstitutionFilter?: boolean;
}

type Filters = {
  category: string;
  event: string;
  actor: string;
  subject: string;
  severity: string;
  institution_id: string;
  'created_at[date_from]': string;
  'created_at[date_to]': string;
};

export default function ActivityLogList({
  activityLogs,
  filterOptions,
  institutions = [],
  showInstitutionFilter = false,
}: Props) {
  const [selected, setSelected] = useState<ActivityLog | null>(null);
  const [filters, setFilters] = useState<Filters>(() => currentFilters());

  function currentFilters(): Filters {
    const params = new URL(window.location.href).searchParams;

    return {
      category: params.get('category') ?? '',
      event: params.get('event') ?? '',
      actor: params.get('actor') ?? '',
      subject: params.get('subject') ?? '',
      severity: params.get('severity') ?? '',
      institution_id: params.get('institution_id') ?? '',
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

  const headers: ServerPaginatedTableHeader<ActivityLog>[] = useMemo(
    () => [
      {
        label: 'Time',
        sortKey: 'createdAt',
        render: (row) => formatAsDate(row.created_at, dateTimeFormat),
      },
      {
        label: 'Category',
        value: 'category',
        sortKey: 'category',
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
        render: (row) => <Badge>{row.severity}</Badge>,
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
            <FormLabel>Subject</FormLabel>
            <Input
              value={filters.subject}
              onChange={(e) => updateFilter('subject', e.target.value)}
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
        </SimpleGrid>
        <HStack mt={4}>
          <Button colorScheme="brand" onClick={applyFilters}>
            Filter
          </Button>
          <Button variant="outline" onClick={clearFilters}>
            Clear
          </Button>
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
                <Text fontWeight="semibold">Subject</Text>
                <Text>{activityLog.subject_name ?? 'N/A'}</Text>
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
              </Grid>
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
