import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { TermDayReason, TermDetail } from '@/types/models';
import { PaginationResponse, WeekDay } from '@/types/types';
import {
  Button,
  Checkbox,
  CheckboxGroup,
  Divider,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  Icon,
  IconButton,
  Input,
  SimpleGrid,
  Text,
  VStack,
} from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import InputForm from '@/components/forms/input-form';
import FormControlBox from '@/components/forms/form-control-box';
import { BrandButton } from '@/components/buttons';
import { Div } from '@/components/semantic';
import { dateFormat, ucFirst } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import DateTimeDisplay from '@/components/date-time-display';
import { format } from 'date-fns';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PencilIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/outline';

interface Props {
  termDetail: TermDetail;
  termDetails: PaginationResponse<TermDetail>;
}

const weekDayLabels = Object.keys(WeekDay).map((day) => day.substring(0, 3));

export default function ListTermDetails({ termDetail, termDetails }: Props) {
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
          href={instRoute('term-details.index', [row.id])}
          variant={'ghost'}
          colorScheme={'brand'}
        />
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabBody>
          <Div maxWidth={'500px'}>
            <UpdateTermDetail termDetail={termDetail} />
          </Div>
        </SlabBody>
      </Slab>
      <br />
      <Slab>
        <SlabHeading title="Term Results" />
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

function UpdateTermDetail({ termDetail }: { termDetail: TermDetail }) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    start_date: termDetail.start_date
      ? format(new Date(termDetail.start_date), dateFormat)
      : '',
    end_date: termDetail.end_date
      ? format(new Date(termDetail.end_date), dateFormat)
      : '',
    expected_attendance_count: String(
      termDetail.expected_attendance_count ?? ''
    ),
    next_term_resumption_date: termDetail.next_term_resumption_date
      ? format(new Date(termDetail.next_term_resumption_date), dateFormat)
      : '',
    inactive_weekdays: (termDetail.inactive_weekdays ?? []).map((day) =>
      typeof day === 'string' ? Number(day) : day
    ),
    special_active_days: termDetail.special_active_days ?? [],
    inactive_days: termDetail.inactive_days ?? [],
  });

  const cleanDayReasons = (days: TermDayReason[]) =>
    (days ?? [])
      .filter((day) => day.date || day.reason)
      .map((day) => ({
        ...day,
        reason: (day.reason ?? '').trim(),
      }));

  const cleanWeekdays = (weekdays: (number | string)[]) =>
    (weekdays ?? [])
      .map((day) => Number(day))
      .filter((day) => Number.isInteger(day));

  const onSubmit = async () => {
    const payload = {
      ...webForm.data,
      start_date: webForm.data.start_date || null,
      end_date: webForm.data.end_date || null,
      next_term_resumption_date: webForm.data.next_term_resumption_date || null,
      expected_attendance_count:
        webForm.data.expected_attendance_count === ''
          ? null
          : webForm.data.expected_attendance_count,
      inactive_weekdays: cleanWeekdays(webForm.data.inactive_weekdays ?? []),
      special_active_days: cleanDayReasons(
        (webForm.data.special_active_days as TermDayReason[]) ?? []
      ),
      inactive_days: cleanDayReasons(
        (webForm.data.inactive_days as TermDayReason[]) ?? []
      ),
    };
    const res = await webForm.submit((_data, web) =>
      web.put(instRoute('term-details.update', [termDetail]), payload)
    );
    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['termDetails'] });
  };
  const title = `Update ${termDetail.academic_session?.title} ${
    termDetail.for_mid_term ? 'Mid-' : ''
  } ${ucFirst(termDetail.term)} Term Detail`;
  return (
    <VStack spacing={2} align={'start'}>
      <Text fontWeight={'bold'} fontSize={'20px'}>
        {title}
      </Text>
      <Divider />
      <FormControlBox
        form={webForm as any}
        title="Opening Date"
        formKey="start_date"
      >
        <Input
          type={'date'}
          value={webForm.data.start_date}
          onChange={(e) =>
            webForm.setValue('start_date', e.currentTarget.value)
          }
        />
      </FormControlBox>
      <FormControlBox
        form={webForm as any}
        title="Closing Date"
        formKey="end_date"
      >
        <Input
          type={'date'}
          value={webForm.data.end_date}
          onChange={(e) => webForm.setValue('end_date', e.currentTarget.value)}
        />
      </FormControlBox>
      <FormControlBox
        form={webForm as any}
        title="Next Term Resumption Date [Optional]"
        formKey="next_term_resumption_date"
      >
        <Input
          type={'date'}
          value={webForm.data.next_term_resumption_date}
          onChange={(e) =>
            webForm.setValue('next_term_resumption_date', e.currentTarget.value)
          }
        />
      </FormControlBox>
      <InputForm
        form={webForm as any}
        formKey="expected_attendance_count"
        title="No of Times School Held"
        onChange={(e) =>
          webForm.setValue('expected_attendance_count', e.currentTarget.value)
        }
      />
      <FormControlBox
        form={webForm as any}
        title="Inactive Weekdays"
        formKey="inactive_weekdays"
      >
        <CheckboxGroup
          colorScheme={'brand'}
          value={(webForm.data.inactive_weekdays ?? []).map((day) =>
            String(day)
          )}
          onChange={(values) =>
            webForm.setValue(
              'inactive_weekdays' as any,
              (values as string[]).map((day) => Number(day))
            )
          }
        >
          <SimpleGrid columns={[2, 3]} spacing={1} mt={1}>
            {Object.entries(WeekDay).map(([key, value]) => (
              <Checkbox key={value} value={String(value)}>
                {key}
              </Checkbox>
            ))}
          </SimpleGrid>
        </CheckboxGroup>
        <Text fontSize={'sm'} color={'gray.600'} mt={1}>
          Days school is usually closed (e.g. weekends).
        </Text>
      </FormControlBox>
      <DayReasonList
        title="Special Active Days"
        description="Days when school opens on normally inactive weekdays."
        formKey="special_active_days"
        items={(webForm.data.special_active_days as TermDayReason[]) ?? []}
        onChange={(items) =>
          webForm.setValue('special_active_days' as any, items)
        }
        form={webForm as any}
      />
      <DayReasonList
        title="Inactive Days"
        description="Days school will close outside the inactive weekdays."
        formKey="inactive_days"
        items={(webForm.data.inactive_days as TermDayReason[]) ?? []}
        onChange={(items) => webForm.setValue('inactive_days' as any, items)}
        form={webForm as any}
      />
      <BrandButton
        colorScheme={'brand'}
        onClick={onSubmit}
        isLoading={webForm.processing}
      >
        Save
      </BrandButton>
    </VStack>
  );
}

function DayReasonList({
  title,
  description,
  items,
  onChange,
  formKey,
  form,
}: {
  title: string;
  description: string;
  items: TermDayReason[];
  onChange: (items: TermDayReason[]) => void;
  formKey: string;
  form: any;
}) {
  const list = items ?? [];

  const updateItem = (
    index: number,
    key: keyof TermDayReason,
    value: string
  ) => {
    const next = [...list];
    next[index] = { ...(next[index] ?? {}), [key]: value };
    onChange(next);
  };

  const removeItem = (index: number) => {
    const next = list.filter((_, idx) => idx !== index);
    onChange(next);
  };

  const addItem = () => onChange([...list, { date: '', reason: '' }]);

  const getError = (index: number, key: keyof TermDayReason) =>
    (form.errors as any)?.[`${formKey}.${index}.${key}`];

  return (
    <VStack
      spacing={3}
      align={'stretch'}
      w={'100%'}
      border={'1px solid'}
      borderRadius={'7px'}
      borderColor={'gray.200'}
      p={4}
    >
      <VStack spacing={0} align={'start'}>
        <Text fontWeight={'semibold'}>{title}</Text>
        {description && (
          <Text fontSize={'sm'} color={'gray.600'}>
            {description}
          </Text>
        )}
      </VStack>
      {list.length === 0 && (
        <Text fontSize={'sm'} color={'gray.500'}>
          No days added yet.
        </Text>
      )}
      {list.map((item, index) => (
        <HStack key={index} align={'start'} spacing={3}>
          <FormControl
            isInvalid={!!getError(index, 'date')}
            minW={['auto', '170px']}
          >
            <FormLabel mb={0}>Date</FormLabel>
            <Input
              type={'date'}
              value={item?.date ?? ''}
              onChange={(e) => updateItem(index, 'date', e.currentTarget.value)}
            />
            <FormErrorMessage>{getError(index, 'date')}</FormErrorMessage>
          </FormControl>
          <FormControl isInvalid={!!getError(index, 'reason')} flex={1}>
            <FormLabel mb={0}>Reason</FormLabel>
            <Input
              value={item?.reason ?? ''}
              onChange={(e) =>
                updateItem(index, 'reason', e.currentTarget.value)
              }
            />
            <FormErrorMessage>{getError(index, 'reason')}</FormErrorMessage>
          </FormControl>
          <IconButton
            aria-label={`Remove ${title}`}
            icon={<Icon as={TrashIcon} />}
            variant={'ghost'}
            colorScheme={'red'}
            onClick={() => removeItem(index)}
          />
        </HStack>
      ))}
      <Button
        leftIcon={<Icon as={PlusIcon} />}
        variant={'outline'}
        colorScheme={'brand'}
        alignSelf={'start'}
        onClick={addItem}
      >
        Add Day
      </Button>
    </VStack>
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
