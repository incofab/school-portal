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
import CourseSelect from '@/components/selectors/course-select';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { FormButton } from '@/components/buttons';
import FormControlBox from '@/components/forms/form-control-box';
import useInstitutionRoute from '@/hooks/use-institution-route';
import PagePrintLayout from '@/domain/institutions/page-print-layout';
import ImagePaths from '@/util/images';
import ResultUtil from '@/util/result-util';

type TermKey = 'first' | 'second' | 'third';

interface ReportScore {
  score: number | null;
  position: number | null;
}

interface SingleSubjectReportRow {
  student: Student;
  student_id: number;
  term_results: Record<TermKey, ReportScore>;
  overall: ReportScore;
}

interface Props {
  classification?: Classification;
  academicSession?: AcademicSession;
  course?: Course;
  singleSubjectReport: SingleSubjectReportRow[];
}

const TERMS: { key: TermKey; label: string }[] = [
  { key: 'first', label: 'First Term' },
  { key: 'second', label: 'Second Term' },
  { key: 'third', label: 'Third Term' },
];

export default function SingleSubjectReportSheet({
  classification,
  academicSession,
  course,
  singleSubjectReport,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const canShow = Boolean(classification && academicSession && course);

  return (
    <PagePrintLayout
      filename={`single-subject-report-${classification?.id ?? ''}-${
        academicSession?.id ?? ''
      }-${course?.id ?? ''}.pdf`}
      contentId={'single-subject-report-sheet'}
    >
      <Div
        mx={'auto'}
        px={3}
        py={2}
        maxWidth={'1200px'}
        id={'single-subject-report-sheet'}
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
                      {academicSession?.title} - {course?.title}{' '}
                    </>
                  ) : (
                    ''
                  )}
                  Single Subject Report
                </Text>
              </VStack>
            </HStack>
          </Div>
          <ClassSessionAndSubjectSelector
            classification={classification}
            academicSession={academicSession}
            course={course}
            onSubmit={(data, onFinish) =>
              Inertia.visit(instRoute('reports.single-subject-report', data), {
                onFinish,
              })
            }
          />
          {canShow && (
            <TableContainer mt={2} overflowX={'auto'}>
              <Table className="result-table" size={'sm'}>
                <Thead>
                  <Tr>
                    <Th rowSpan={2}>Student</Th>
                    {TERMS.map((term) => (
                      <Th key={term.key} colSpan={2} textAlign={'center'}>
                        {term.label}
                      </Th>
                    ))}
                    <Th colSpan={2} textAlign={'center'}>
                      Overall
                    </Th>
                  </Tr>
                  <Tr>
                    {TERMS.map((term) => (
                      <React.Fragment key={term.key}>
                        <Th isNumeric>Score</Th>
                        <Th isNumeric>Position</Th>
                      </React.Fragment>
                    ))}
                    <Th isNumeric>Average</Th>
                    <Th isNumeric>Position</Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {singleSubjectReport.map((row) => (
                    <Tr key={row.student_id}>
                      <Td>
                        {row.student.user?.full_name ??
                          row.student.full_code ??
                          row.student.code}
                      </Td>
                      {TERMS.map((term) => (
                        <React.Fragment key={term.key}>
                          <Td isNumeric>
                            {formatScore(row.term_results[term.key]?.score)}
                          </Td>
                          <Td isNumeric>
                            {formatPosition(
                              row.term_results[term.key]?.position
                            )}
                          </Td>
                        </React.Fragment>
                      ))}
                      <Td isNumeric>{formatScore(row.overall.score)}</Td>
                      <Td isNumeric>{formatPosition(row.overall.position)}</Td>
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

function ClassSessionAndSubjectSelector({
  classification,
  academicSession,
  course,
  onSubmit,
}: {
  classification?: Classification;
  academicSession?: AcademicSession;
  course?: Course;
  onSubmit: (
    data: {
      classification: string | number;
      academicSession: string | number;
      course: string | number;
    },
    onFinish: () => void
  ) => void;
}) {
  const { currentAcademicSession } = useSharedProps();
  const [isLoading, setIsLoading] = useState(false);
  const webForm = useWebForm({
    academicSession: academicSession?.id ?? currentAcademicSession.id,
    classification: classification?.id ?? '',
    course: course?.id ?? '',
  });

  const submit = () => {
    setIsLoading(true);
    onSubmit(
      {
        classification: webForm.data.classification,
        academicSession: webForm.data.academicSession,
        course: webForm.data.course,
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
      <FormControlBox
        form={webForm as any}
        formKey={'course'}
        title="Subject"
        isRequired
      >
        <CourseSelect
          selectValue={webForm.data.course}
          onChange={(e: any) => webForm.setValue('course', e.value)}
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
