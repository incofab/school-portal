import { LinkButton } from '@/components/buttons';
import DateTimeDisplay from '@/components/date-time-display';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useInstitutionRoute from '@/hooks/use-institution-route';
import DashboardLayout from '@/layout/dashboard-layout';
import { TermDayReason, TermDetail } from '@/types/models';
import { PaginationResponse, WeekDay } from '@/types/types';
import { dateFormat } from '@/util/util';
import { Icon, IconButton, Text } from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PencilIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import startCase from 'lodash/startCase';

interface Props {
  termDetails: PaginationResponse<TermDetail>;
}

const weekDayLabels = Object.keys(WeekDay).map((day) => day.substring(0, 3));

export default function ListTermDetails({ termDetails }: Props) {
  const { instRoute } = useInstitutionRoute();
  const headers: ServerPaginatedTableHeader<TermDetail>[] = [
    {
      label: 'Academic Session',
      value: 'academic_session.title',
    },
    {
      label: 'Term',
      value: 'term',
      render: (row) => (
        <Text>
          {startCase(row.term)} {row.for_mid_term ? 'Mid-' : ''}Term
        </Text>
      ),
    },
    {
      label: 'Start Date',
      value: 'start_date',
      render: (row) =>
        row.start_date ? (
          <DateTimeDisplay
            dateTime={row.start_date}
            dateTimeformat={dateFormat}
          />
        ) : null,
    },
    {
      label: 'End Date',
      value: 'end_date',
      render: (row) =>
        row.end_date ? (
          <DateTimeDisplay
            dateTime={row.end_date}
            dateTimeformat={dateFormat}
          />
        ) : null,
    },
    {
      label: 'Inactive Weekdays',
      render: (row) => (
        <Text fontSize={'sm'}>{formatWeekdays(row.inactive_weekdays)}</Text>
      ),
    },
    {
      label: 'Special Active Days',
      render: (row) => (
        <Text fontSize={'sm'}>{formatDayReasons(row.special_active_days)}</Text>
      ),
    },
    {
      label: 'Inactive Days',
      render: (row) => (
        <Text fontSize={'sm'}>{formatDayReasons(row.inactive_days)}</Text>
      ),
    },
    {
      label: 'School Held',
      value: 'expected_attendance_count',
    },
    {
      label: 'Exam Result',
      value: 'result_exam_mode',
      render: (row) => (
        <Text fontSize={'sm'}>
          {row.result_exam_mode ? startCase(row.result_exam_mode) : 'Inherit'}
        </Text>
      ),
    },
    {
      label: 'Next Term Resumption',
      value: 'next_term_resumption_date',
      render: (row) =>
        row.next_term_resumption_date ? (
          <DateTimeDisplay
            dateTime={row.next_term_resumption_date}
            dateTimeformat={dateFormat}
          />
        ) : null,
    },
    {
      label: 'Action',
      render: (row) => (
        <IconButton
          as={InertiaLink}
          aria-label={'Edit Term Detail'}
          icon={<Icon as={PencilIcon} />}
          href={instRoute('term-details.edit', [row.id])}
          variant={'ghost'}
          colorScheme={'brand'}
        />
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Term Details"
          rightElement={
            <LinkButton
              title="Create Term Detail"
              href={instRoute('term-details.create')}
            />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={termDetails.data}
            keyExtractor={(row) => row.id}
            paginator={termDetails}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function formatWeekdays(days?: number[]) {
  if (!days || days.length === 0) {
    return 'None';
  }
  return days
    .slice()
    .sort((a, b) => a - b)
    .map((day) => weekDayLabels[Number(day)] ?? day)
    .join(', ');
}

function formatDayReasons(days?: TermDayReason[]) {
  if (!days || days.length === 0) {
    return 'None';
  }
  return days
    .map((day) => `${format(new Date(day.date), dateFormat)} (${day.reason})`)
    .join('; ');
}
