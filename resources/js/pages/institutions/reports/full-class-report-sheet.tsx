import React, { useState } from 'react';
import {
  AcademicSession,
  Assessment,
  Classification,
  Course,
  Student,
} from '@/types/models';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import {
  Alert,
  AlertIcon,
  Avatar,
  Box,
  FormControl,
  Stack,
  Table,
  TableContainer,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
  VStack,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import ClassificationSelect from '@/components/selectors/classification-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import { preventNativeSubmit, ucFirst } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { FormButton } from '@/components/buttons';
import FormControlBox from '@/components/forms/form-control-box';
import useInstitutionRoute from '@/hooks/use-institution-route';
import PagePrintLayout from '@/domain/institutions/page-print-layout';
import ImagePaths from '@/util/images';
import ResultUtil from '@/util/result-util';
import { TermType } from '@/types/types';
import EnumSelect from '@/components/dropdown-select/enum-select';
import startCase from 'lodash/startCase';

interface ReportCourse extends Course {
  assessments: Assessment[];
}

interface SubjectResult {
  assessments: Record<string, number | string | null>;
  exam: number | null;
  total: number | null;
}

interface FullClassReportRow {
  student: Student;
  student_id: number;
  subject_results: Record<number, SubjectResult>;
  overall_total_score: number | null;
  average: number | null;
  position: number | null;
}

interface FullClassReport {
  courses: ReportCourse[];
  students: FullClassReportRow[];
}

interface Props {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: TermType;
  fullClassReport: FullClassReport;
}

const STICKY_STUDENT_COLUMN = {
  position: 'sticky' as const,
  left: 0,
  zIndex: 2,
  bg: 'white',
  boxShadow: '1px 0 0 #000',
};

export default function FullClassReportSheet({
  classification,
  academicSession,
  term,
  fullClassReport,
}: Props) {
  const canShow = Boolean(classification && academicSession && term);
  const courses = fullClassReport.courses ?? [];
  const rows = fullClassReport.students ?? [];
  const filename = `full-class-report-c${classification?.id ?? ''}-a${
    academicSession?.id ?? ''
  }-t${term ?? ''}`;

  return (
    <PagePrintLayout
      filename={`${filename}.pdf`}
      contentId={'full-class-report-sheet'}
      exportToExcel
      excelSheetName="Full Class Report"
    >
      <Div
        mx={'auto'}
        px={3}
        py={2}
        maxWidth={'1500px'}
        w={'full'}
        minW={0}
        id={'full-class-report-sheet'}
      >
        <VStack align={'stretch'} spacing={3} minW={0}>
          <ReportHeader
            classification={classification}
            academicSession={academicSession}
            term={term}
          />

          <ClassSessionAndTermSelector
            classification={classification}
            academicSession={academicSession}
            term={term}
          />

          {canShow && rows.length > 0 && courses.length > 0 && (
            <TableContainer
              border={'1px solid'}
              borderColor={'gray.300'}
              overflowX={'auto'}
              w={'full'}
              maxW={'100%'}
              minW={0}
              className="full-class-report-table-container"
              sx={{ WebkitOverflowScrolling: 'touch' }}
            >
              <Table
                className="result-table full-class-report-table"
                size={'sm'}
                minW={'max-content'}
                sx={{
                  'th, td': {
                    whiteSpace: 'nowrap',
                    verticalAlign: 'middle',
                  },
                  'thead th': {
                    bg: 'gray.100',
                  },
                  '@media print': {
                    '.sticky-student-column': {
                      position: 'static',
                      boxShadow: 'none',
                    },
                  },
                }}
              >
                <Thead>
                  <Tr>
                    <Th
                      rowSpan={2}
                      minW={'230px'}
                      className="sticky-student-column"
                      sx={{
                        ...STICKY_STUDENT_COLUMN,
                        zIndex: 4,
                        bg: 'gray.100',
                      }}
                    >
                      Student
                    </Th>
                    {courses.map((course) => (
                      <Th
                        key={course.id}
                        colSpan={course.assessments.length + 2}
                        textAlign={'center'}
                      >
                        {course.title}
                      </Th>
                    ))}
                    <Th rowSpan={2} isNumeric>
                      Total
                    </Th>
                    <Th rowSpan={2} isNumeric>
                      Avg
                    </Th>
                    <Th rowSpan={2} isNumeric>
                      Pos
                    </Th>
                  </Tr>
                  <Tr>
                    {courses.map((course) => (
                      <React.Fragment key={course.id}>
                        {course.assessments.map((assessment) => (
                          <Th
                            key={`${course.id}-${assessment.result_key}`}
                            isNumeric
                          >
                            <VerticalHeader
                              text={startCase(assessment.raw_title)}
                            />
                          </Th>
                        ))}
                        <Th isNumeric>
                          <VerticalHeader text="Exam Score" />
                        </Th>
                        <Th isNumeric>
                          <VerticalHeader text="Subject Total" />
                        </Th>
                      </React.Fragment>
                    ))}
                  </Tr>
                </Thead>
                <Tbody>
                  {rows.map((row) => (
                    <Tr key={row.student_id}>
                      <Td
                        className="sticky-student-column"
                        sx={STICKY_STUDENT_COLUMN}
                        fontWeight={'medium'}
                      >
                        {studentName(row.student)}
                      </Td>
                      {courses.map((course) => {
                        const subjectResult = row.subject_results[course.id];

                        return (
                          <React.Fragment key={course.id}>
                            {course.assessments.map((assessment) => (
                              <Td
                                key={`${course.id}-${assessment.result_key}`}
                                isNumeric
                              >
                                {formatScore(
                                  subjectResult?.assessments[
                                    assessment.result_key
                                  ]
                                )}
                              </Td>
                            ))}
                            <Td isNumeric>
                              {formatScore(subjectResult?.exam)}
                            </Td>
                            <Td isNumeric>
                              {formatScore(subjectResult?.total)}
                            </Td>
                          </React.Fragment>
                        );
                      })}
                      <Td isNumeric>{formatScore(row.overall_total_score)}</Td>
                      <Td isNumeric>{formatScore(row.average)}</Td>
                      <Td isNumeric>{formatPosition(row.position)}</Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </TableContainer>
          )}

          {canShow && (rows.length === 0 || courses.length === 0) && (
            <Alert status="info" className="hidden-on-print">
              <AlertIcon />
              No full class report data was found for the selected class,
              session, and term.
            </Alert>
          )}
        </VStack>
      </Div>
    </PagePrintLayout>
  );
}

function ReportHeader({
  classification,
  academicSession,
  term,
}: {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: TermType;
}) {
  const { currentInstitution } = useSharedProps();
  const canShow = Boolean(classification && academicSession && term);

  return (
    <Div className="result-sheet-header">
      <Stack
        background={'#FAFAFA'}
        p={2}
        direction={{ base: 'column', md: 'row' }}
      >
        <Avatar
          size={'2xl'}
          name="Institution logo"
          src={currentInstitution.photo ?? ImagePaths.default_school_logo}
          mx={'auto'}
        />
        <VStack spacing={1} align={'stretch'} width={'full'}>
          <Text fontSize={'2xl'} fontWeight={'bold'} textAlign={'center'}>
            {currentInstitution.name}
          </Text>
          <Text textAlign={'center'} fontSize={'18px'} whiteSpace={'nowrap'}>
            {currentInstitution.address}
            <br /> {currentInstitution.email}
          </Text>
          <Text fontWeight={'semibold'} textAlign={'center'} fontSize={'18px'}>
            {canShow ? (
              <>
                {classification?.title}
                {' - '}
                {academicSession?.title}
                {' - '}
                {ucFirst(term!)} Term{' '}
              </>
            ) : (
              ''
            )}
            Full Class Report
          </Text>
        </VStack>
      </Stack>
    </Div>
  );
}

function ClassSessionAndTermSelector({
  classification,
  academicSession,
  term,
}: {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: TermType;
}) {
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const [isLoading, setIsLoading] = useState(false);
  const webForm = useWebForm({
    term: term ?? currentTerm,
    academicSession: academicSession?.id ?? currentAcademicSession.id,
    classification: classification?.id ?? '',
  });

  const submit = () => {
    setIsLoading(true);
    Inertia.visit(
      instRoute('reports.full-class-report', {
        classification: webForm.data.classification,
        academicSession: webForm.data.academicSession,
        term: webForm.data.term,
      }),
      {
        onFinish: () => setIsLoading(false),
      }
    );
  };

  return (
    <Wrap
      align={'end'}
      as={'form'}
      spacing={2}
      onSubmit={preventNativeSubmit(submit)}
      justify={'center'}
      className="hidden-on-print"
    >
      <WrapItem minW={'170px'}>
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
      </WrapItem>
      <WrapItem minW={'170px'}>
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
      </WrapItem>
      <WrapItem minW={'150px'}>
        <FormControlBox form={webForm as any} formKey={'term'} title="Term">
          <EnumSelect
            enumData={TermType}
            selectValue={webForm.data.term}
            onChange={(e: any) => webForm.setValue('term', e.value)}
          />
        </FormControlBox>
      </WrapItem>
      <WrapItem>
        <FormControl>
          <FormButton
            isLoading={isLoading || webForm.processing}
            marginTop={'35px'}
            variant={'outline'}
            className="hidden-on-print"
          />
        </FormControl>
      </WrapItem>
    </Wrap>
  );
}

function VerticalHeader({ text }: { text: string }) {
  return (
    <Box minH={'120px'} display={'flex'} alignItems={'center'}>
      <Text className="vertical-header" overflow={'hidden'}>
        {text}
      </Text>
    </Box>
  );
}

function studentName(student: Student) {
  return student.user?.full_name ?? student.full_code ?? student.code;
}

function formatScore(score?: number | string | null) {
  return score === undefined || score === null || score === '' ? '-' : score;
}

function formatPosition(position?: number | null) {
  return position === undefined || position === null
    ? '-'
    : ResultUtil.formatPosition(position);
}
