import React, { useState } from 'react';
import {
  Checkbox,
  Divider,
  FormControl,
  FormLabel,
  Input,
  Spacer,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import {
  AcademicSession,
  Assessment,
  CourseResult,
  CourseTeacher,
} from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import FormControlBox from '@/components/forms/form-control-box';
import {
  Nullable,
  PaginationResponse,
  SelectOptionType,
  TermType,
} from '@/types/types';
import EnumSelect from '@/components/dropdown-select/enum-select';
import StudentSelect from '@/components/selectors/student-select';
import Dt from '@/components/dt';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import startCase from 'lodash/startCase';

interface Props {
  courseTeacher: CourseTeacher;
  courseResult?: CourseResult;
  academicSession?: AcademicSession;
  term?: TermType;
  for_mid_term?: boolean;
  courseResults?: PaginationResponse<CourseResult>;
  assessments: Assessment[];
}

export default function RecordCourseResult({
  courseResult,
  courseTeacher,
  academicSession,
  term,
  for_mid_term,
  courseResults,
  assessments,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const {
    currentAcademicSessionId,
    currentTerm,
    usesMidTermResult,
    lockTermSession,
  } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const [assessmentValue, setAssessmentValue] = useState<{
    [key: string]: number | string;
  }>(courseResult?.assessment_values ?? {});

  const webForm = useWebForm({
    academic_session_id:
      courseResult?.academic_session?.id ??
      academicSession?.id ??
      currentAcademicSessionId,
    term: courseResult?.term ?? term ?? currentTerm,
    for_mid_term: courseResult?.for_mid_term ?? for_mid_term ?? false,
    result: {
      student_id: courseResult?.student
        ? {
            label: courseResult.student.user?.full_name,
            value: courseResult.student_id,
          }
        : ({} as Nullable<SelectOptionType<number>>),
      exam: courseResult?.exam ?? '',
    },
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('course-results.store', [courseTeacher]), {
        ...data,
        result: [
          {
            ...data.result,
            student_id: data.result.student_id?.value,
            exam: data.result.exam ?? 0,
            ass: assessmentValue,
          },
        ],
      });
    });

    if (!handleResponseToast(res)) return;

    if (courseResult) {
      Inertia.visit(instRoute('course-results.index'));
    } else {
      webForm.reset();
      setAssessmentValue({});
      Inertia.reload();
    }
  };

  const details: SelectOptionType[] = [
    { label: 'Subject', value: courseTeacher.course?.title ?? '' },
    { label: 'Class', value: courseTeacher.classification?.title ?? '' },
    { label: 'Teacher', value: courseTeacher.user?.full_name ?? '' },
  ];

  return (
    <DashboardLayout>
      <Div>
        <CenteredBox>
          <Slab>
            <SlabHeading
              title={`${courseResult ? 'Update' : 'Record'} Result`}
            />
            <SlabBody>
              <Dt contentData={details} />
              <Divider height={1} my={2} />
              <VStack
                spacing={4}
                as={'form'}
                onSubmit={preventNativeSubmit(submit)}
              >
                <FormControlBox
                  form={webForm as any}
                  title="Academic Session"
                  formKey="academic_session_id"
                >
                  <AcademicSessionSelect
                    selectValue={webForm.data.academic_session_id}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue('academic_session_id', e?.value)
                    }
                    required
                    isDisabled={lockTermSession}
                  />
                </FormControlBox>
                <FormControlBox
                  form={webForm as any}
                  title="Term"
                  formKey="term"
                >
                  <EnumSelect
                    enumData={TermType}
                    selectValue={webForm.data.term}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) => webForm.setValue('term', e?.value)}
                    required
                    isDisabled={lockTermSession}
                  />
                </FormControlBox>
                <FormControlBox
                  form={webForm as any}
                  title="Student"
                  formKey="result.student_id"
                >
                  <StudentSelect
                    value={webForm.data.result.student_id}
                    isMulti={false}
                    isClearable={true}
                    classification={courseTeacher.classification_id}
                    onChange={(e: any) =>
                      webForm.setValue('result', {
                        ...webForm.data.result,
                        student_id: e,
                      })
                    }
                    required
                  />
                </FormControlBox>
                {assessments.map((assessment) => {
                  if (
                    assessment.term &&
                    assessment.term !== webForm.data.term
                  ) {
                    return null;
                  }
                  return (
                    <FormControl key={assessment.raw_title + webForm.data.term}>
                      <FormLabel>{startCase(assessment.raw_title)}</FormLabel>
                      <Input
                        disabled={assessment.depends_on == null ? false : true}
                        value={assessmentValue[assessment.raw_title] ?? ''}
                        onChange={(e) =>
                          setAssessmentValue({
                            ...assessmentValue,
                            [assessment.raw_title]: e.currentTarget.value,
                          })
                        }
                      />
                    </FormControl>
                  );
                })}
                <FormControlBox
                  form={webForm as any}
                  formKey="result.exam"
                  title="Exam"
                >
                  <Input
                    value={webForm.data.result.exam}
                    onChange={(e) =>
                      webForm.setValue('result', {
                        ...webForm.data.result,
                        exam: e.currentTarget.value,
                      })
                    }
                  />
                </FormControlBox>
                {usesMidTermResult && (
                  <FormControlBox
                    form={webForm as any}
                    formKey="for_mid_term"
                    title=""
                  >
                    <Checkbox
                      isChecked={webForm.data.for_mid_term}
                      onChange={(e) =>
                        webForm.setValue(
                          'for_mid_term',
                          e.currentTarget.checked
                        )
                      }
                    >
                      For Mid-Term Result
                    </Checkbox>
                  </FormControlBox>
                )}
                <FormControl>
                  <FormButton isLoading={webForm.processing} />
                </FormControl>
              </VStack>
            </SlabBody>
          </Slab>
        </CenteredBox>
        {courseResults && (
          <>
            <Spacer height={4} />
            <ListTeachersCourseResults courseResults={courseResults} />
          </>
        )}
      </Div>
    </DashboardLayout>
  );
}

function ListTeachersCourseResults({
  courseResults,
}: {
  courseResults: PaginationResponse<CourseResult>;
}) {
  const headers: ServerPaginatedTableHeader<CourseResult>[] = [
    {
      label: 'Student',
      value: 'student.user.full_name',
    },
    {
      label: 'Subject',
      value: 'course.title',
    },
    {
      label: 'Session/Term',
      render: (row) =>
        `${row.academic_session?.title} - ${startCase(row.term)} ${
          row.for_mid_term ? 'Mid Term' : ''
        }`,
    },
    {
      label: 'Assessment',
      render: function (row) {
        return Object.entries(row.assessment_values ?? {})
          .map(([key, val]) => `${startCase(key)} = ${val}`)
          .join(',\n');
      },
    },
    {
      label: 'Exam',
      value: 'exam',
    },
    {
      label: 'Result',
      value: 'result',
    },
    {
      label: 'Grade',
      value: 'grade',
    },
  ];

  return (
    <Slab>
      <SlabHeading title="Student Results" />
      <SlabBody>
        <ServerPaginatedTable
          scroll={true}
          headers={headers}
          data={courseResults.data}
          keyExtractor={(row) => row.id}
          paginator={courseResults}
          hideSearchField={true}
        />
      </SlabBody>
    </Slab>
  );
}
