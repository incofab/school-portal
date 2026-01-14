import React from 'react';
import { AcademicSession, Classification, Course } from '@/types/models';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import { Avatar, FormControl, HStack, Text, VStack } from '@chakra-ui/react';
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

interface Props {
  classification?: Classification;
  academicSession?: AcademicSession;
  term?: TermType;
  subjectReport: SubjectReportRow[];
}

export default function SubjectReportSheet({
  classification,
  academicSession,
  term,
  subjectReport,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const { instRoute } = useInstitutionRoute();

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
  return (
    <PagePrintLayout
      filename={`subject-report-${classification?.id ?? ''}-${
        academicSession?.id ?? ''
      }-${term ?? ''}.pdf`}
      contentId={'subject-report-sheet'}
    >
      <Div
        mx={'auto'}
        px={3}
        py={2}
        maxWidth={'1200px'}
        id={'subject-report-sheet'}
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
                      {academicSession?.title
                        ? `${academicSession.title}`
                        : ''}{' '}
                      {term ? `${ucFirst(term)} Term ` : ''}
                    </>
                  ) : (
                    ''
                  )}
                  Subject Report
                </Text>
              </VStack>
            </HStack>
          </Div>
          <ClassAndSessionSelector
            classification={classification}
            academicSession={academicSession}
            term={term}
            onSubmit={(data) =>
              Inertia.visit(instRoute('reports.subject-report', data))
            }
          />
          {canShow && (
            <Div mt={2}>
              <DataTable
                scroll={true}
                headers={headers}
                data={subjectReport}
                keyExtractor={(row) => row.course_id}
                hideSearchField={true}
                tableProps={{ className: 'result-table' }}
              />
            </Div>
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
  term?: string;
  onSubmit: (data: {
    classification: string | number;
    academicSession: string | number;
    term: string;
  }) => void;
}) {
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const webForm = useWebForm({
    term: term ?? currentTerm,
    academicSession: academicSession?.id ?? currentAcademicSession.id,
    classification: classification?.id ?? '',
  });

  const submit = () => {
    onSubmit({
      classification: webForm.data.classification,
      academicSession: webForm.data.academicSession,
      term: webForm.data.term,
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
