import React from 'react';
import { AcademicSession, Classification } from '@/types/models';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import {
  Avatar,
  Box,
  Checkbox,
  FormControl,
  HStack,
  Table,
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
import { preventNativeSubmit, ucFirst } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { FormButton } from '@/components/buttons';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { TermType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import PagePrintLayout from '@/domain/institutions/page-print-layout';
import ImagePaths from '@/util/images';

interface GradeReportItem {
  grade: string;
  count: number;
  percentage: number;
}

interface SubjectGradeReportRow {
  course_id: number;
  course_title: string;
  grades: Record<string, number>;
}

interface Props {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: string;
  forMidTerm: boolean;
  gradeReport: GradeReportItem[];
  subjectGradeReport: {
    grades: string[];
    rows: SubjectGradeReportRow[];
  };
}

export default function GradeReportSheet({
  classification,
  academicSession,
  term,
  forMidTerm,
  gradeReport,
  subjectGradeReport,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const canShow = Boolean(classification && academicSession && term);
  const totalStudents = gradeReport.reduce((acc, item) => acc + item.count, 0);

  return (
    <PagePrintLayout
      filename={`grade-report-${classification?.id ?? ''}-${
        academicSession?.id ?? ''
      }-${term ?? ''}-${forMidTerm ? 'mid' : 'full'}.pdf`}
      contentId={'grade-report-sheet'}
    >
      <Div
        mx={'auto'}
        px={3}
        py={2}
        maxWidth={'1200px'}
        id={'grade-report-sheet'}
      >
        <VStack align={'stretch'} spacing={4}>
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
                      {academicSession?.title ?? ''}{' '}
                      {term ? `${ucFirst(term)} ` : ''}
                      {forMidTerm ? 'Mid-Term ' : 'Term '}
                    </>
                  ) : (
                    ''
                  )}
                  Grade Report
                </Text>
              </VStack>
            </HStack>
          </Div>

          <GradeReportSelector
            classification={classification}
            academicSession={academicSession}
            term={term}
            forMidTerm={forMidTerm}
            onSubmit={(data) =>
              Inertia.visit(instRoute('reports.grade-report', data))
            }
          />

          {canShow && (
            <VStack align={'stretch'} spacing={6}>
              <Box>
                <Text fontWeight={'bold'} mb={3}>
                  Grade Report Summary
                </Text>
                <Table size="sm" className="result-table">
                  <Thead>
                    <Tr>
                      <Th>Grade</Th>
                      <Th isNumeric>Students</Th>
                      <Th isNumeric>Percentage (%)</Th>
                    </Tr>
                  </Thead>
                  <Tbody>
                    {gradeReport.map((item) => (
                      <Tr key={item.grade}>
                        <Td>{item.grade}</Td>
                        <Td isNumeric>{item.count}</Td>
                        <Td isNumeric>{item.percentage}%</Td>
                      </Tr>
                    ))}
                    <Tr>
                      <Td fontWeight={'bold'}>Total</Td>
                      <Td isNumeric fontWeight={'bold'}>
                        {totalStudents}
                      </Td>
                      <Td isNumeric fontWeight={'bold'}>
                        {totalStudents > 0 ? '100%' : '0%'}
                      </Td>
                    </Tr>
                  </Tbody>
                </Table>
              </Box>

              <Box>
                <Text fontWeight={'bold'} mb={3}>
                  Subject Grade Matrix
                </Text>
                <Table size="sm" className="result-table">
                  <Thead>
                    <Tr>
                      <Th>Subject</Th>
                      {subjectGradeReport.grades.map((grade) => (
                        <Th key={grade} isNumeric>
                          {grade}
                        </Th>
                      ))}
                    </Tr>
                  </Thead>
                  <Tbody>
                    {subjectGradeReport.rows.length > 0 ? (
                      subjectGradeReport.rows.map((row) => (
                        <Tr key={row.course_id}>
                          <Td>{row.course_title}</Td>
                          {subjectGradeReport.grades.map((grade) => (
                            <Td key={grade} isNumeric>
                              {row.grades[grade] ?? 0}
                            </Td>
                          ))}
                        </Tr>
                      ))
                    ) : (
                      <Tr>
                        <Td
                          colSpan={Math.max(
                            subjectGradeReport.grades.length + 1,
                            2
                          )}
                        >
                          <Text textAlign={'center'} py={2}>
                            No results found
                          </Text>
                        </Td>
                      </Tr>
                    )}
                  </Tbody>
                </Table>
              </Box>
            </VStack>
          )}
        </VStack>
      </Div>
    </PagePrintLayout>
  );
}

function GradeReportSelector({
  classification,
  academicSession,
  term,
  forMidTerm,
  onSubmit,
}: {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: string;
  forMidTerm: boolean;
  onSubmit: (data: {
    classification: string | number;
    academicSession: string | number;
    term: string;
    forMidTerm: boolean;
  }) => void;
}) {
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const webForm = useWebForm({
    term: term ?? currentTerm,
    academicSession: academicSession?.id ?? currentAcademicSession.id,
    classification: classification?.id ?? '',
    forMidTerm,
  });

  const submit = () => {
    onSubmit({
      classification: webForm.data.classification,
      academicSession: webForm.data.academicSession,
      term: webForm.data.term,
      forMidTerm: webForm.data.forMidTerm,
    });
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
        formKey={'term'}
        title="Term"
        isRequired
      >
        <EnumSelect
          enumData={TermType}
          selectValue={webForm.data.term}
          onChange={(e: any) => webForm.setValue('term', e.value)}
          required
        />
      </FormControlBox>
      <FormControlBox form={webForm as any} formKey={'forMidTerm'} title="">
        <Checkbox
          isChecked={webForm.data.forMidTerm}
          onChange={(e) =>
            webForm.setValue('forMidTerm', e.currentTarget.checked)
          }
          mb={3}
        >
          For Mid-Term Result
        </Checkbox>
      </FormControlBox>
      <FormControl>
        <FormButton
          isLoading={webForm.processing}
          marginTop={'35px'}
          variant={'outline'}
        />
      </FormControl>
    </HStack>
  );
}
