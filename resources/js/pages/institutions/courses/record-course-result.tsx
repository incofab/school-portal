import React from 'react';
import { Divider, FormControl, Input, Spacer, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { AcademicSession, CourseResult, CourseTeacher } from '@/types/models';
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
  courseResults?: PaginationResponse<CourseResult>;
}

export default function RecordCourseResult({
  courseResult,
  courseTeacher,
  academicSession,
  term,
  courseResults,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    academic_session_id: academicSession?.id ?? currentAcademicSession,
    term: term ?? currentTerm,
    result: {
      student_id: courseResult?.student
        ? {
            label: courseResult.student.user?.full_name,
            value: courseResult.student_id,
          }
        : ({} as Nullable<SelectOptionType<number>>),
      first_assessment: courseResult?.first_assessment ?? '',
      second_assessment: courseResult?.second_assessment ?? '',
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
            first_assessment: data.result.first_assessment ?? 0,
            second_assessment: data.result.second_assessment ?? 0,
            exam: data.result.exam ?? 0,
          },
        ],
      });
    });

    if (!handleResponseToast(res)) return;

    if (courseResult) {
      Inertia.visit(instRoute('course-results.index'));
    } else {
      webForm.setValue('result', {
        student_id: {} as SelectOptionType<number>,
        first_assessment: '',
        second_assessment: '',
        exam: '',
      });
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
                      webForm.setValue('academic_session_id', e.value)
                    }
                    required
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
                    onChange={(e: any) => webForm.setValue('term', e.value)}
                    required
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
                <FormControlBox
                  form={webForm as any}
                  formKey="result.first_assessment"
                  title="Assessment 1"
                >
                  <Input
                    value={webForm.data.result.first_assessment}
                    onChange={(e) =>
                      webForm.setValue('result', {
                        ...webForm.data.result,
                        first_assessment: e.currentTarget.value,
                      })
                    }
                  />
                </FormControlBox>
                <FormControlBox
                  form={webForm as any}
                  formKey="result.second_assessment"
                  title="Assessment 2"
                >
                  <Input
                    value={webForm.data.result.second_assessment}
                    onChange={(e) =>
                      webForm.setValue('result', {
                        ...webForm.data.result,
                        second_assessment: e.currentTarget.value,
                      })
                    }
                  />
                </FormControlBox>
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
        `${row.academic_session?.title} - ${startCase(row.term)}`,
    },
    {
      label: '1st CA',
      value: 'first_assessment',
    },
    {
      label: '2nd CA',
      value: 'second_assessment',
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
