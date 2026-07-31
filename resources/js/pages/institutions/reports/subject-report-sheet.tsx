import React, { useState } from 'react';
import { AcademicSession, Classification, Course } from '@/types/models';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import {
  Avatar,
  FormControl,
  HStack,
  Stack,
  Text,
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
import DataTable, { TableHeader } from '@/components/data-table';
import PagePrintLayout from '@/domain/institutions/page-print-layout';
import ImagePaths from '@/util/images';

const ALL_TERMS = 'all-terms';
type TermSelection = TermType | typeof ALL_TERMS;

interface SubjectReportRow {
  course: Course;
  course_id: number;
  num_of_students: number;
  total_score: number;
  max_obtainable_score: number;
  max_score: number;
  min_score: number;
  average: number;
  // pass_count: number;
  highest_score: number | null;
  highest_student: string | null;
  lowest_score: number | null;
  lowest_student: string | null;
  teachers: string[];
}

interface SubjectReportSection {
  key: string;
  title: string;
  subjectReport: SubjectReportRow[];
}

interface Props {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: TermSelection;
  subjectReport: SubjectReportRow[];
  subjectReportSections?: SubjectReportSection[];
}

export default function SubjectReportSheet({
  classification,
  academicSession,
  term,
  subjectReport,
  subjectReportSections,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const isAllTerms = term === ALL_TERMS;
  const reportSections =
    subjectReportSections && subjectReportSections.length > 0
      ? subjectReportSections
      : [
          {
            key: term ?? 'selected-term',
            title: term && !isAllTerms ? `${ucFirst(term)} Term` : 'Summary',
            subjectReport,
          },
        ];

  const headers: TableHeader<SubjectReportRow>[] = [
    {
      label: 'Subject',
      value: 'course.title',
    },
    {
      label: 'Teacher(s)',
      render: (row) => row.teachers.join(', '),
    },
    {
      label: 'Students',
      value: 'num_of_students',
    },
    {
      label: 'Total Score',
      value: 'total_score',
    },
    {
      label: 'Max Obtainable',
      value: 'max_obtainable_score',
    },
    {
      label: 'Max Score',
      value: 'max_score',
    },
    {
      label: 'Min Score',
      value: 'min_score',
    },
    {
      label: 'Average',
      value: 'average',
    },
    // {
    //   label: 'Pass',
    //   value: 'pass_count',
    // },
    {
      label: 'Highest',
      render: (row) =>
        row.highest_student
          ? `${row.highest_student} (${row.highest_score ?? '-'})`
          : '-',
    },
    {
      label: 'Lowest',
      render: (row) =>
        row.lowest_student
          ? `${row.lowest_student} (${row.lowest_score ?? '-'})`
          : '-',
    },
  ];
  const canShow = Boolean(classification);
  const filename = `subject-report-${classification?.id ?? ''}-${
    academicSession?.id ?? ''
  }-${term ?? ''}`;

  return (
    <PagePrintLayout
      filename={`${filename}.pdf`}
      contentId={'subject-report-sheet'}
      exportToExcel
      excelSheetName="Subject Report"
    >
      <Div
        mx={'auto'}
        px={3}
        py={2}
        maxWidth={'1200px'}
        w={'full'}
        minW={0}
        id={'subject-report-sheet'}
      >
        <VStack align={'stretch'} spacing={2} minW={0}>
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
                      {academicSession?.title
                        ? `${academicSession.title}`
                        : ''}{' '}
                      {term
                        ? isAllTerms
                          ? 'All Terms '
                          : `${ucFirst(term)} Term `
                        : ''}
                    </>
                  ) : (
                    ''
                  )}
                  Subject Report
                </Text>
              </VStack>
            </Stack>
          </Div>
          <ClassAndSessionSelector
            classification={classification}
            academicSession={academicSession}
            term={term}
            onSubmit={(data, onFinish) =>
              Inertia.visit(instRoute('reports.subject-report', data), {
                onFinish,
              })
            }
          />
          {canShow && (
            <VStack align={'stretch'} spacing={8} mt={2} minW={0}>
              {reportSections.map((section) => (
                <Div key={section.key}>
                  {isAllTerms && (
                    <Text
                      fontSize={'lg'}
                      fontWeight={'semibold'}
                      mb={2}
                      textAlign={'center'}
                    >
                      {section.title}
                    </Text>
                  )}
                  <DataTable
                    scroll={true}
                    headers={headers}
                    data={section.subjectReport}
                    keyExtractor={(row) => row.course_id}
                    hideSearchField={true}
                    tableProps={{ className: 'result-table' }}
                  />
                </Div>
              ))}
            </VStack>
          )}
        </VStack>
      </Div>
    </PagePrintLayout>
  );
}

function ClassAndSessionSelector({
  classification,
  academicSession,
  term,
  onSubmit,
}: {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: TermSelection;
  onSubmit: (
    data: {
      classification: string | number;
      academicSession: string | number;
      term: string;
    },
    onFinish: () => void
  ) => void;
}) {
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const [isLoading, setIsLoading] = useState(false);
  const webForm = useWebForm({
    term: term ?? currentTerm,
    academicSession: academicSession?.id ?? currentAcademicSession.id,
    classification: classification?.id ?? '',
  });

  const submit = () => {
    setIsLoading(true);
    onSubmit(
      {
        classification: webForm.data.classification,
        academicSession: webForm.data.academicSession,
        term: webForm.data.term,
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
        formKey={'term'}
        title="Term"
        isRequired
      >
        <EnumSelect
          enumData={TermType}
          additionalEnumData={{ AllTerms: ALL_TERMS }}
          selectValue={webForm.data.term}
          onChange={(e: any) => webForm.setValue('term', e.value)}
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
