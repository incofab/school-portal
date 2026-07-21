import React, { useState } from 'react';
import {
  AcademicSession,
  Classification,
  Course,
  Student,
} from '@/types/models';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import {
  Avatar,
  FormControl,
  HStack,
  Table,
  TableContainer,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import ClassificationSelect from '@/components/selectors/classification-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { FormButton } from '@/components/buttons';
import FormControlBox from '@/components/forms/form-control-box';
import useInstitutionRoute from '@/hooks/use-institution-route';
import PagePrintLayout from '@/domain/institutions/page-print-layout';
import ImagePaths from '@/util/images';
import ResultUtil from '@/util/result-util';

type TermKey = 'first' | 'second' | 'third';

interface SubjectResult {
  terms: Record<TermKey, number | null>;
  total: number | null;
  average: number | null;
  position: number | null;
}

interface ClassSubjectResultReportRow {
  student: Student;
  student_id: number;
  subject_results: Record<number, SubjectResult>;
}

interface ClassSubjectResultReport {
  courses: Course[];
  students: ClassSubjectResultReportRow[];
}

interface Props {
  classification?: Classification;
  academicSession?: AcademicSession;
  classSubjectResultReport: ClassSubjectResultReport;
}

const TERMS: { key: TermKey; label: string }[] = [
  { key: 'first', label: 'T1' },
  { key: 'second', label: 'T2' },
  { key: 'third', label: 'T3' },
];

export default function ClassSubjectResultReportSheet({
  classification,
  academicSession,
  classSubjectResultReport,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const canShow = Boolean(classification && academicSession);
  const courses = classSubjectResultReport.courses ?? [];
  const rows = classSubjectResultReport.students ?? [];

  return (
    <PagePrintLayout
      filename={`class-subject-result-report-${classification?.id ?? ''}-${
        academicSession?.id ?? ''
      }.pdf`}
      contentId={'class-subject-result-report-sheet'}
    >
      <Div
        mx={'auto'}
        px={3}
        py={2}
        maxWidth={'1400px'}
        id={'class-subject-result-report-sheet'}
      >
        <VStack align={'stretch'} spacing={2}>
          <Div className="result-sheet-header">
            <HStack background={'#FAFAFA'} p={2}>
              <Avatar
                size={'2xl'}
                name="Institution logo"
                src={currentInstitution.photo ?? ImagePaths.default_school_logo}
              />
              <VStack spacing={1} align={'stretch'} width={'full'}>
                <Text fontSize={'2xl'} fontWeight={'bold'} textAlign={'center'}>
                  {currentInstitution.name}
                </Text>
                <Text
                  textAlign={'center'}
                  fontSize={'18px'}
                  whiteSpace={'nowrap'}
                >
                  {currentInstitution.address}
                  <br /> {currentInstitution.email}
                </Text>
                <Text
                  fontWeight={'semibold'}
                  textAlign={'center'}
                  fontSize={'18px'}
                >
                  {canShow ? (
                    <>
                      {classification?.title}
                      {' - '}
                      {academicSession?.title}{' '}
                    </>
                  ) : (
                    ''
                  )}
                  Full Subject Result Report
                </Text>
              </VStack>
            </HStack>
          </Div>
          <ClassAndSessionSelector
            classification={classification}
            academicSession={academicSession}
            onSubmit={(data, onFinish) =>
              Inertia.visit(
                instRoute('reports.class-subject-result-report', data),
                { onFinish }
              )
            }
          />
          {canShow && (
            <TableContainer mt={2} overflowX={'auto'}>
              <Table className="result-table" size={'sm'}>
                <Thead>
                  <Tr>
                    <Th rowSpan={2} minW={'220px'}>
                      Student
                    </Th>
                    {courses.map((course) => (
                      <Th key={course.id} colSpan={6} textAlign={'center'}>
                        {course.title}
                      </Th>
                    ))}
                  </Tr>
                  <Tr>
                    {courses.map((course) => (
                      <React.Fragment key={course.id}>
                        {TERMS.map((term) => (
                          <Th key={`${course.id}-${term.key}`} isNumeric>
                            {term.label}
                          </Th>
                        ))}
                        <Th isNumeric>Total</Th>
                        <Th isNumeric>Avg</Th>
                        <Th isNumeric>Pos</Th>
                      </React.Fragment>
                    ))}
                  </Tr>
                </Thead>
                <Tbody>
                  {rows.map((row) => (
                    <Tr key={row.student_id}>
                      <Td>
                        {row.student.user?.full_name ??
                          row.student.full_code ??
                          row.student.code}
                      </Td>
                      {courses.map((course) => {
                        const result = row.subject_results[course.id];

                        return (
                          <React.Fragment key={course.id}>
                            {TERMS.map((term) => (
                              <Td key={`${course.id}-${term.key}`} isNumeric>
                                {formatScore(result?.terms[term.key])}
                              </Td>
                            ))}
                            <Td isNumeric>{formatScore(result?.total)}</Td>
                            <Td isNumeric>{formatScore(result?.average)}</Td>
                            <Td isNumeric>
                              {formatPosition(result?.position)}
                            </Td>
                          </React.Fragment>
                        );
                      })}
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </TableContainer>
          )}
        </VStack>
      </Div>
    </PagePrintLayout>
  );
}

function ClassAndSessionSelector({
  classification,
  academicSession,
  onSubmit,
}: {
  classification?: Classification;
  academicSession?: AcademicSession;
  onSubmit: (
    data: {
      classification: string | number;
      academicSession: string | number;
    },
    onFinish: () => void
  ) => void;
}) {
  const { currentAcademicSession } = useSharedProps();
  const [isLoading, setIsLoading] = useState(false);
  const webForm = useWebForm({
    academicSession: academicSession?.id ?? currentAcademicSession.id,
    classification: classification?.id ?? '',
  });

  const submit = () => {
    setIsLoading(true);
    onSubmit(
      {
        classification: webForm.data.classification,
        academicSession: webForm.data.academicSession,
      },
      () => setIsLoading(false)
    );
  };

  return (
    <HStack
      align={'end'}
      as={'form'}
      w={'full'}
      spacing={2}
      onSubmit={preventNativeSubmit(submit)}
      className="hidden-on-print"
    >
      <FormControlBox
        form={webForm as any}
        formKey={'classification'}
        title="Class"
        isRequired
      >
        <ClassificationSelect
          selectValue={webForm.data.classification}
          onChange={(e: any) => webForm.setValue('classification', e.value)}
          required
        />
      </FormControlBox>
      <FormControlBox
        form={webForm as any}
        formKey={'academicSession'}
        title="Academic Session"
        isRequired
      >
        <AcademicSessionSelect
          selectValue={webForm.data.academicSession}
          onChange={(e: any) => webForm.setValue('academicSession', e.value)}
          required
        />
      </FormControlBox>
      <FormControl>
        <FormButton
          isLoading={isLoading || webForm.processing}
          marginTop={'35px'}
          variant={'outline'}
        />
      </FormControl>
    </HStack>
  );
}

function formatScore(score?: number | null) {
  return score === undefined || score === null ? '-' : score;
}

function formatPosition(position?: number | null) {
  return position === undefined || position === null
    ? '-'
    : ResultUtil.formatPosition(position);
}
