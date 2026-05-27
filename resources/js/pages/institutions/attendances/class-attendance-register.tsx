import React, { useEffect, useMemo, useState } from 'react';
import {
  Badge,
  Box,
  Button,
  HStack,
  Input,
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
import {
  addDays,
  addWeeks,
  format,
  startOfWeek,
  subDays,
  subWeeks,
} from 'date-fns';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FormControlBox from '@/components/forms/form-control-box';
import ClassificationSelect from '@/components/selectors/classification-select';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useWebForm from '@/hooks/use-web-form';
import { Attendance, InstitutionUser } from '@/types/models';
import { Nullable, SelectOptionType } from '@/types/types';
import { SingleValue } from 'react-select';

type Mode = 'day' | 'week';

interface AttendanceCell {
  id: number;
  signed_in_at?: string | null;
  signed_out_at?: string | null;
  remark?: string | null;
}

interface RegisterData {
  students: InstitutionUser[];
  days: { date: string; label: string; day: string }[];
  attendance: Record<string, Record<string, AttendanceCell>>;
  start_date: string;
  end_date: string;
  mode: Mode;
}

export default function ClassAttendanceRegister() {
  const { instRoute } = useInstitutionRoute();
  const [mode, setMode] = useState<Mode>('week');
  const [date, setDate] = useState(format(new Date(), 'yyyy-MM-dd'));
  const [classification, setClassification] =
    useState<Nullable<SingleValue<SelectOptionType<number>>>>(null);
  const [register, setRegister] = useState<RegisterData | null>(null);
  const webForm = useWebForm({});

  useEffect(() => {
    if (!classification?.value) {
      setRegister(null);
      return;
    }

    webForm
      .submit((data, web) =>
        web.get(instRoute('attendances.class-register', []), {
          params: {
            mode,
            date,
            classification_id: classification.value,
          },
        })
      )
      .then((res) => {
        setRegister(res.ok ? res.data.result : null);
      });
  }, [classification?.value, date, mode]);

  const periodLabel = useMemo(() => {
    if (!register) return '';
    return register.mode === 'week'
      ? `${format(new Date(register.start_date), 'MMM d')} - ${format(
          new Date(register.end_date),
          'MMM d, yyyy'
        )}`
      : format(new Date(register.start_date), 'EEEE, MMM d, yyyy');
  }, [register]);

  const movePeriod = (direction: 'previous' | 'next') => {
    const currentDate = new Date(date);
    const nextDate =
      mode === 'week'
        ? direction === 'next'
          ? addWeeks(currentDate, 1)
          : subWeeks(currentDate, 1)
        : direction === 'next'
        ? addDays(currentDate, 1)
        : subDays(currentDate, 1);

    setDate(format(nextDate, 'yyyy-MM-dd'));
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Class Attendance Register" />
        <SlabBody>
          <VStack align="stretch" spacing={5}>
            <SimpleGrid columns={{ base: 1, md: 3 }} spacing={4}>
              <FormControlBox form={webForm as any} title="Mode" formKey="mode">
                <HStack>
                  <Button
                    size="sm"
                    variant={mode === 'day' ? 'solid' : 'outline'}
                    onClick={() => setMode('day')}
                  >
                    Day
                  </Button>
                  <Button
                    size="sm"
                    variant={mode === 'week' ? 'solid' : 'outline'}
                    onClick={() => {
                      setMode('week');
                      setDate(
                        format(
                          startOfWeek(new Date(date), { weekStartsOn: 1 }),
                          'yyyy-MM-dd'
                        )
                      );
                    }}
                  >
                    Week
                  </Button>
                </HStack>
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Class"
                formKey="classification"
              >
                <ClassificationSelect
                  selectValue={classification}
                  onChange={(value: any) => setClassification(value)}
                  isClearable
                  isMulti={false}
                />
              </FormControlBox>

              <FormControlBox form={webForm as any} title="Date" formKey="date">
                <Input
                  type="date"
                  value={date}
                  onChange={(e) => setDate(e.currentTarget.value)}
                />
              </FormControlBox>
            </SimpleGrid>

            <HStack justify="space-between">
              <HStack>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => movePeriod('previous')}
                >
                  Previous {mode === 'week' ? 'Week' : 'Day'}
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => movePeriod('next')}
                >
                  Next {mode === 'week' ? 'Week' : 'Day'}
                </Button>
              </HStack>
              <Text fontWeight="semibold">{periodLabel}</Text>
            </HStack>

            <RegisterSummary
              register={register}
              isLoading={webForm.processing}
            />
            <RegisterTable register={register} />
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function RegisterSummary({
  register,
  isLoading,
}: {
  register: RegisterData | null;
  isLoading: boolean;
}) {
  const markedCount = register
    ? register.students.reduce((count, student) => {
        return (
          count +
          register.days.filter(
            (day) => !!register.attendance[String(student.id)]?.[day.date]
          ).length
        );
      }, 0)
    : 0;
  const possibleCount = register
    ? register.students.length * register.days.length
    : 0;

  return (
    <SimpleGrid columns={{ base: 1, md: 3 }} spacing={3}>
      <SummaryPill label="Students" value={register?.students.length ?? 0} />
      <SummaryPill label="Marked Cells" value={markedCount} />
      <SummaryPill
        label="Coverage"
        value={
          isLoading
            ? 'Loading'
            : possibleCount > 0
            ? `${Math.round((markedCount / possibleCount) * 100)}%`
            : '0%'
        }
      />
    </SimpleGrid>
  );
}

function SummaryPill({ label, value }: { label: string; value: any }) {
  return (
    <Box borderWidth="1px" borderRadius="md" p={3}>
      <Text color="gray.500" fontSize="xs" textTransform="uppercase">
        {label}
      </Text>
      <Text fontWeight="bold" fontSize="lg">
        {value}
      </Text>
    </Box>
  );
}

function RegisterTable({ register }: { register: RegisterData | null }) {
  if (!register) {
    return (
      <Box borderWidth="1px" borderRadius="md" p={6}>
        <Text color="gray.600">Select a class to view attendance.</Text>
      </Box>
    );
  }

  return (
    <Box overflowX="auto" borderWidth="1px" borderRadius="md">
      <Table size="sm">
        <Thead>
          <Tr>
            <Th minW="240px">Student</Th>
            {register.days.map((day) => (
              <Th key={day.date} minW="150px" textAlign="center">
                <Text>{day.label}</Text>
                <Text color="gray.500" fontWeight="normal">
                  {day.day}
                </Text>
              </Th>
            ))}
          </Tr>
        </Thead>
        <Tbody>
          {register.students.map((student) => (
            <Tr key={student.id}>
              <Td>
                <Text fontWeight="semibold">{student.user?.full_name}</Text>
                <Text color="gray.500" fontSize="sm">
                  {student.student?.classification?.title}
                </Text>
              </Td>
              {register.days.map((day) => (
                <Td key={day.date}>
                  <AttendanceCellView
                    attendance={
                      register.attendance[String(student.id)]?.[day.date]
                    }
                  />
                </Td>
              ))}
            </Tr>
          ))}
        </Tbody>
      </Table>
    </Box>
  );
}

function AttendanceCellView({
  attendance,
}: {
  attendance?: AttendanceCell | Attendance;
}) {
  if (!attendance) {
    return (
      <VStack align="center" spacing={1}>
        <Badge colorScheme="red">Absent</Badge>
      </VStack>
    );
  }

  return (
    <VStack align="center" spacing={1}>
      <Badge colorScheme={attendance.signed_out_at ? 'green' : 'blue'}>
        {attendance.signed_out_at ? 'Complete' : 'Present'}
      </Badge>
      <Text fontSize="xs">
        {format(new Date(attendance.signed_in_at!), 'H:mm')} -{' '}
        {attendance.signed_out_at
          ? format(new Date(attendance.signed_out_at), 'H:mm')
          : 'Open'}
      </Text>
    </VStack>
  );
}
