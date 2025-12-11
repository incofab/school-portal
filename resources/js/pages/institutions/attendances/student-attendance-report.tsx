import React from 'react';
import {
  Box,
  Button,
  Divider,
  HStack,
  Icon,
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
import useInstitutionRoute from '@/hooks/use-institution-route';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FormControlBox from '@/components/forms/form-control-box';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import {
  Attendance,
  AcademicSession,
  TermDetail,
  InstitutionUser,
} from '@/types/models';
import { SelectOptionType, TermType } from '@/types/types';
import { dateFormat, formatAsDate } from '@/util/util';
import { format } from 'date-fns';
import { PrinterIcon } from '@heroicons/react/24/outline';
import { Div } from '@/components/semantic';
import { useState } from 'react';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import EnumSelect from '@/components/dropdown-select/enum-select';
import InstitutionUserSelect from '@/components/selectors/institution-user-select';
import useSharedProps from '@/hooks/use-shared-props';

interface ReportData {
  attendance: Attendance[];
  attendance_days: string[];
  lower_bound?: string | null;
  upper_bound?: string | null;
  active_days?: number | null;
  expected_attendance?: number | null;
  actual_attendance?: number;
  institution_user: InstitutionUser;
  termDetail: TermDetail;
  term: TermType;
  academicSession: AcademicSession;
}

interface Props {
  academicSessions: AcademicSession[];
}

export default function StudentAttendanceReport({ academicSessions }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const [reportData, setReportData] = useState<ReportData | null>(null);

  const webForm = useWebForm({
    term: currentTerm,
    institution_user_id: {} as SelectOptionType<number>,
    academic_session_id: {
      label: currentAcademicSession.title,
      value: currentAcademicSession.id,
    } as SelectOptionType<number>,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('attendance-reports.retrieve', []), {
        ...data,
        institution_user_id: data.institution_user_id?.value,
        academic_session_id: data.academic_session_id?.value,
      })
    );
    if (!handleResponseToast(res)) return;

    setReportData(res.data.report);
  };

  const canShowReport = !!reportData && !webForm.processing;

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Student Attendance Report" />
        <SlabBody>
          <VStack align={'stretch'} spacing={4} className="print-hidden">
            <SimpleGrid columns={[1, 3]} spacing={3}>
              <FormControlBox
                form={webForm as any}
                title="Staff/Student"
                formKey="institution_user_id"
              >
                <InstitutionUserSelect
                  value={webForm.data.institution_user_id}
                  isClearable
                  isMulti={false}
                  onChange={(e: any) =>
                    webForm.setValue('institution_user_id', e)
                  }
                />
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                title="Academic Session"
                formKey="academic_session_id"
              >
                <AcademicSessionSelect
                  selectValue={webForm.data.academic_session_id}
                  academicSessions={academicSessions}
                  onChange={(e: any) =>
                    webForm.setValue('academic_session_id', e)
                  }
                  isClearable
                />
              </FormControlBox>
              <FormControlBox form={webForm as any} title="Term" formKey="term">
                <EnumSelect
                  enumData={TermType}
                  selectValue={webForm.data.term}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('term', e?.value)}
                />
              </FormControlBox>
            </SimpleGrid>
            <HStack justify={'flex-end'}>
              <Button
                colorScheme="brand"
                onClick={submit}
                isLoading={webForm.processing}
              >
                View Report
              </Button>
            </HStack>
          </VStack>
          <Divider my={4} />
          {canShowReport ? (
            <ReportView report={reportData!} />
          ) : (
            <Text color={'gray.600'}>
              Select student, academic session, and term to view attendance.
            </Text>
          )}
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function ReportView({ report }: { report: ReportData }) {
  const periodLabel =
    report.lower_bound && report.upper_bound
      ? `${formatAsDate(report.lower_bound)} → ${formatAsDate(
          report.upper_bound
        )}`
      : 'Not specified';

  return (
    <VStack align={'stretch'} spacing={4}>
      <HStack justify={'space-between'} className="print-hidden">
        <Text fontSize={'lg'} fontWeight={'semibold'}>
          {report.institution_user.user?.full_name} •{' '}
          {/* {report.institution_user.classification?.title} */}
        </Text>
        <Button
          leftIcon={<Icon as={PrinterIcon} />}
          onClick={() => window.print()}
          variant={'outline'}
        >
          Print
        </Button>
      </HStack>
      <Box>
        <Text fontWeight={'semibold'}>Summary</Text>
        <SimpleGrid columns={[1, 2, 4]} spacing={3} mt={2}>
          <SummaryItem
            label="Attendance Recorded"
            value={report.actual_attendance ?? 0}
          />
          <SummaryItem
            label="Expected Attendance"
            value={report.expected_attendance ?? '—'}
          />
          <SummaryItem
            label="Active Days In Range"
            value={report.active_days ?? '—'}
          />
          <SummaryItem label="Period" value={periodLabel} />
        </SimpleGrid>
        <Text color={'gray.600'} mt={2} fontSize={'sm'}>
          Term window: {formatTermDetailRange(report.termDetail)} • Expected
          count: {report.termDetail.expected_attendance_count ?? 'Not set'}
        </Text>
      </Box>

      <Slab>
        <SlabHeading title="Attendance Records" />
        <SlabBody>
          {report.attendance.length === 0 ? (
            <Text color={'gray.600'}>
              No attendance recorded for selection.
            </Text>
          ) : (
            <Div overflowX={'auto'}>
              <Table variant="simple" size={'sm'}>
                <Thead>
                  <Tr>
                    <Th>Date</Th>
                    <Th>Signed In</Th>
                    <Th>Signed Out</Th>
                    <Th>Recorded By</Th>
                    <Th>Remark</Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {report.attendance.map((item) => (
                    <Tr key={item.id}>
                      <Td>{formatAsDate(item.signed_in_at)}</Td>
                      <Td>{formatDateTime(item.signed_in_at)}</Td>
                      <Td>
                        {item.signed_out_at
                          ? formatDateTime(item.signed_out_at)
                          : '—'}
                      </Td>
                      <Td>{item.staff_user?.user?.full_name ?? '—'}</Td>
                      <Td>{item.remark || '—'}</Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </Div>
          )}
        </SlabBody>
      </Slab>
    </VStack>
  );
}

function SummaryItem({ label, value }: { label: string; value: any }) {
  return (
    <Box borderWidth={'1px'} borderRadius={'md'} p={3}>
      <Text fontSize={'xs'} textTransform={'uppercase'} color={'gray.600'}>
        {label}
      </Text>
      <Text fontWeight={'semibold'} mt={1}>
        {value ?? '—'}
      </Text>
    </Box>
  );
}

function formatDateTime(value?: string) {
  if (!value) return '—';
  return format(new Date(value), `${dateFormat} HH:mm`);
}

function formatTermDetailRange(termDetail: TermDetail) {
  return `${formatAsDate(termDetail.start_date)} → ${formatAsDate(
    termDetail.end_date
  )}`;
}
