import { BrandButton } from '@/components/buttons';
import EnumSelect from '@/components/dropdown-select/enum-select';
import FormControlBox from '@/components/forms/form-control-box';
import InputForm from '@/components/forms/input-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { TermDayReason, TermDetail } from '@/types/models';
import { ResultExamMode, WeekDay } from '@/types/types';
import { dateFormat, ucFirst } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import {
  Badge,
  Box,
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
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';
import React from 'react';

export default function TermDetailForm({
  termDetail,
}: {
  termDetail: TermDetail;
}) {
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
    result_exam_mode: termDetail.result_exam_mode ?? '',
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
      result_exam_mode: webForm.data.result_exam_mode || null,
    };
    const res = await webForm.submit((_data, web) =>
      web.put(instRoute('term-details.update', [termDetail]), payload)
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('term-details.index'));
  };

  const title = `${termDetail.academic_session?.title} ${
    termDetail.for_mid_term ? 'Mid-' : ''
  } ${ucFirst(termDetail.term)} Term Detail`;

  return (
    <VStack spacing={5} align="stretch">
      <Box
        borderWidth="1px"
        borderColor="gray.200"
        borderRadius="8px"
        bg="white"
        p={{ base: 4, md: 5 }}
      >
        <HStack align="start" spacing={4} flexWrap="wrap">
          <Box>
            <Text fontWeight="bold" fontSize={{ base: 'lg', md: 'xl' }}>
              {title}
            </Text>
            <Text fontSize="sm" color="gray.600">
              Control the academic calendar, attendance days, and result display
              rule for this term.
            </Text>
          </Box>
          <Spacer />
          <Badge colorScheme={termDetail.for_mid_term ? 'purple' : 'brand'}>
            {termDetail.for_mid_term ? 'Mid term' : 'Full term'}
          </Badge>
        </HStack>
      </Box>

      <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={4}>
        <TermDetailSection
          title="Term Dates"
          description="Set the term window and the next resumption date shown on reports."
        >
          <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
            <FormControlBox
              form={webForm as any}
              title="Opening Date"
              formKey="start_date"
            >
              <Input
                type="date"
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
                type="date"
                value={webForm.data.end_date}
                onChange={(e) =>
                  webForm.setValue('end_date', e.currentTarget.value)
                }
              />
            </FormControlBox>
            <Box gridColumn={{ md: '1 / -1' }}>
              <FormControlBox
                form={webForm as any}
                title="Next Term Resumption Date"
                formKey="next_term_resumption_date"
              >
                <Input
                  type="date"
                  value={webForm.data.next_term_resumption_date}
                  onChange={(e) =>
                    webForm.setValue(
                      'next_term_resumption_date',
                      e.currentTarget.value
                    )
                  }
                />
              </FormControlBox>
            </Box>
          </SimpleGrid>
        </TermDetailSection>

        <TermDetailSection
          title="Attendance and Results"
          description="Record expected school days and choose how exam scores appear."
        >
          <VStack spacing={4} align="stretch">
            <InputForm
              form={webForm as any}
              formKey="expected_attendance_count"
              title="Number of Times School Held"
              type="number"
              onChange={(e) =>
                webForm.setValue(
                  'expected_attendance_count',
                  e.currentTarget.value
                )
              }
            />
            <FormControlBox
              form={webForm as any}
              title="Show Exam Result"
              formKey="result_exam_mode"
            >
              <EnumSelect
                enumData={ResultExamMode}
                additionalEnumData={{ Inherit: '' }}
                selectValue={webForm.data.result_exam_mode}
                onChange={(e: any) =>
                  webForm.setValue('result_exam_mode' as any, e.value)
                }
                isClearable={true}
              />
              <Text fontSize="sm" color="gray.600" mt={1}>
                Inherit uses the institution result setting for this term.
              </Text>
            </FormControlBox>
          </VStack>
        </TermDetailSection>
      </SimpleGrid>

      <TermDetailSection
        title="Weekly Closure Pattern"
        description="Choose weekdays when school is normally closed. Saturdays and Sundays are selected by default for new term details."
      >
        <FormControlBox
          form={webForm as any}
          title="Inactive Weekdays"
          formKey="inactive_weekdays"
        >
          <CheckboxGroup
            colorScheme="brand"
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
            <SimpleGrid columns={{ base: 2, sm: 3, md: 4, lg: 7 }} spacing={3}>
              {Object.entries(WeekDay).map(([key, value]) => (
                <Box
                  key={value}
                  borderWidth="1px"
                  borderColor="gray.200"
                  borderRadius="7px"
                  px={3}
                  py={2}
                >
                  <Checkbox value={String(value)}>{key}</Checkbox>
                </Box>
              ))}
            </SimpleGrid>
          </CheckboxGroup>
        </FormControlBox>
      </TermDetailSection>

      <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={4}>
        <DayReasonList
          title="Special Active Days"
          description="Dates when school opens even though the weekday is normally inactive."
          formKey="special_active_days"
          items={(webForm.data.special_active_days as TermDayReason[]) ?? []}
          onChange={(items) =>
            webForm.setValue('special_active_days' as any, items)
          }
          form={webForm as any}
        />
        <DayReasonList
          title="Inactive Calendar Days"
          description="One-off closures such as holidays, breaks, or staff training days."
          formKey="inactive_days"
          items={(webForm.data.inactive_days as TermDayReason[]) ?? []}
          onChange={(items) => webForm.setValue('inactive_days' as any, items)}
          form={webForm as any}
        />
      </SimpleGrid>

      <HStack justify="end">
        <BrandButton
          colorScheme="brand"
          onClick={onSubmit}
          isLoading={webForm.processing}
        >
          Save Term Detail
        </BrandButton>
      </HStack>
    </VStack>
  );
}

function TermDetailSection({
  title,
  description,
  children,
}: {
  title: string;
  description: string;
  children: React.ReactNode;
}) {
  return (
    <Box
      borderWidth="1px"
      borderColor="gray.200"
      borderRadius="8px"
      bg="white"
      p={{ base: 4, md: 5 }}
    >
      <VStack align="stretch" spacing={4}>
        <Box>
          <Text fontWeight="semibold">{title}</Text>
          <Text fontSize="sm" color="gray.600">
            {description}
          </Text>
        </Box>
        <Divider />
        {children}
      </VStack>
    </Box>
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
      borderRadius={'8px'}
      borderColor={'gray.200'}
      bg="white"
      p={{ base: 4, md: 5 }}
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
