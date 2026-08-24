import React, { useState } from 'react';
import {
  Badge,
  Box,
  Divider,
  FormControl,
  FormLabel,
  HStack,
  Icon,
  IconButton,
  Input,
  Spinner,
  Spacer,
  Text,
  VStack,
  useColorModeValue,
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
  Student,
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
import SwitchCourseTeacher from './switch-course-teacher-component';
import { InertiaLink } from '@inertiajs/inertia-react';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import DestructivePopover from '@/components/destructive-popover';
import ResultModeSelectionCard from '@/components/result-mode-selection-card';

type AssessmentValue = { [key: string]: number | string };
type SelectionParams = {
  academic_session_id?: number | string | null;
  term?: string | null;
  for_mid_term?: boolean | null;
  student_id?: number | string | null;
};

interface Props {
  courseTeacher: CourseTeacher;
  courseResult?: CourseResult;
  selectedStudent?: Student;
  academicSession?: AcademicSession;
  academic_session_id?: number;
  term?: TermType;
  for_mid_term?: boolean | null;
  courseResults?: PaginationResponse<CourseResult>;
  assessmentGroups: {
    fullTerm: Assessment[];
    midTerm: Assessment[];
  };
  showExamInput?: {
    fullTerm: boolean;
    midTerm: boolean;
  };
  teachersCourses: { [id: number]: CourseTeacher };
}

function getSelectedStudentOption(
  courseResult?: CourseResult,
  selectedStudent?: Student
) {
  const student = courseResult?.student ?? selectedStudent;

  if (!student) {
    return {} as Nullable<SelectOptionType<number>>;
  }

  return {
    label: student.user?.full_name ?? '',
    value: student.id,
  };
}

function visitCreatePage(
  url: string,
  params: SelectionParams,
  onFinish?: () => void
) {
  const nextUrl = new URL(url, window.location.origin);
  const queryParams = {
    academic_session_id: params.academic_session_id,
    term: params.term,
    for_mid_term:
      params.for_mid_term === null ? null : params.for_mid_term ? '1' : '0',
    student_id: params.student_id,
  };

  Object.entries(queryParams).forEach(([key, value]) => {
    if (value === null || value === undefined || value === '') return;
    nextUrl.searchParams.set(key, String(value));
  });

  Inertia.visit(nextUrl.toString(), {
    preserveScroll: true,
    preserveState: false,
    onFinish,
  });
}

export default function RecordCourseResult({
  courseResult,
  selectedStudent,
  courseTeacher,
  academicSession,
  academic_session_id: academicSessionId,
  term,
  for_mid_term: forMidTerm,
  courseResults,
  assessmentGroups,
  showExamInput = { fullTerm: true, midTerm: true },
  teachersCourses,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const selectedCourseTeacherState = useState<CourseTeacher>(courseTeacher);
  const {
    currentAcademicSessionId,
    currentTerm,
    usesMidTermResult,
    lockTermSession,
  } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const [assessmentValue, setAssessmentValue] = useState<AssessmentValue>(
    courseResult?.assessment_values ?? {}
  );
  const [isSelectionLoading, setIsSelectionLoading] = useState(false);
  const hasSelectedResultMode =
    !usesMidTermResult ||
    Boolean(courseResult) ||
    typeof forMidTerm === 'boolean';
  const isResultTypeRequired = usesMidTermResult && !hasSelectedResultMode;
  const createPageUrl = instRoute('course-results.create', [courseTeacher.id]);

  const webForm = useWebForm({
    academic_session_id:
      courseResult?.academic_session?.id ??
      academicSession?.id ??
      academicSessionId ??
      currentAcademicSessionId,
    term: courseResult?.term ?? term ?? currentTerm,
    for_mid_term: courseResult?.for_mid_term ?? forMidTerm ?? false,
    result: {
      student_id: getSelectedStudentOption(courseResult, selectedStudent),
      exam: courseResult?.exam ?? '',
    },
  });
  const isMidTermSelected = Boolean(webForm.data.for_mid_term);
  const selectedAssessments = isMidTermSelected
    ? assessmentGroups.midTerm
    : assessmentGroups.fullTerm;
  const shouldShowExamInput = isMidTermSelected
    ? showExamInput.midTerm
    : showExamInput.fullTerm;
  const resultModeLabel = isMidTermSelected
    ? 'Mid-Term Result'
    : 'Full Term Result';
  const visibleResultModeLabel = hasSelectedResultMode
    ? resultModeLabel
    : 'Select Result Type';
  const resultModeScheme = isMidTermSelected ? 'yellow' : 'blue';
  const resultModeBg = isResultTypeRequired
    ? 'gray.50'
    : isMidTermSelected
    ? 'yellow.50'
    : 'transparent';
  const resultModeBorder = isResultTypeRequired
    ? 'gray.200'
    : isMidTermSelected
    ? 'yellow.200'
    : 'transparent';
  const isFormDisabled = isSelectionLoading || isResultTypeRequired;

  function reloadWithSelection(changes: Partial<SelectionParams>) {
    setIsSelectionLoading(true);
    visitCreatePage(
      createPageUrl,
      {
        academic_session_id:
          changes.academic_session_id ?? webForm.data.academic_session_id,
        term: changes.term ?? webForm.data.term,
        for_mid_term:
          changes.for_mid_term === undefined
            ? hasSelectedResultMode
              ? webForm.data.for_mid_term
              : null
            : changes.for_mid_term,
        student_id:
          changes.student_id === undefined
            ? webForm.data.result.student_id?.value
            : changes.student_id,
      },
      () => setIsSelectionLoading(false)
    );
  }

  function updateAssessmentValue(resultKey: string, value: string) {
    setAssessmentValue((current) => ({
      ...current,
      [resultKey]: value,
    }));
  }

  const submit = async () => {
    if (isResultTypeRequired) return;

    const res = await webForm.submit((data, web) => {
      const { exam, ...resultData } = data.result;
      return web.post(instRoute('course-results.store', [courseTeacher]), {
        ...data,
        result: [
          {
            ...resultData,
            student_id: data.result.student_id?.value,
            ...(shouldShowExamInput ? { exam: exam ?? 0 } : {}),
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
  const disabledBg = useColorModeValue('gray.100', 'gray.700');
  return (
    <DashboardLayout>
      <Div>
        <CenteredBox>
          <Slab backgroundColor={isFormDisabled ? disabledBg : undefined}>
            <SlabHeading
              title={`${courseResult ? 'Update' : 'Record'} Result`}
            />
            <SlabBody>
              {usesMidTermResult && (
                <ResultModeSelectionCard
                  value={
                    isMidTermSelected
                      ? 'mid-term'
                      : hasSelectedResultMode
                      ? 'full-term'
                      : ''
                  }
                  isDisabled={isSelectionLoading}
                  error={webForm.errors.for_mid_term}
                  onChange={(value) => {
                    if (isSelectionLoading) return;
                    const nextForMidTerm = value === 'mid-term';
                    webForm.setValue('for_mid_term', nextForMidTerm);
                    reloadWithSelection({
                      for_mid_term: nextForMidTerm,
                    });
                  }}
                  mb={2}
                />
              )}
              <SwitchCourseTeacher
                courseTeacher={courseTeacher}
                teachersCourses={teachersCourses}
                selectedCourseTeacherState={selectedCourseTeacherState}
                getUrl={(courseTeacherId) =>
                  instRoute('course-results.create', [courseTeacherId])
                }
                isDisabled={isFormDisabled}
              />
              <Dt contentData={details} />
              <Divider height={1} my={2} />
              {selectedCourseTeacherState[0].id === courseTeacher.id && (
                <>
                  {/* {usesMidTermResult && (
                    <ResultModeSelectionCard
                      value={
                        isMidTermSelected
                          ? 'mid-term'
                          : hasSelectedResultMode
                          ? 'full-term'
                          : ''
                      }
                      isDisabled={isSelectionLoading}
                      error={webForm.errors.for_mid_term}
                      onChange={(value) => {
                        if (isSelectionLoading) return;
                        const nextForMidTerm = value === 'mid-term';
                        webForm.setValue('for_mid_term', nextForMidTerm);
                        reloadWithSelection({
                          for_mid_term: nextForMidTerm,
                        });
                      }}
                    />
                  )} */}
                  <Box
                    as="div"
                    aria-disabled={isResultTypeRequired}
                    opacity={isResultTypeRequired ? 0.55 : 1}
                    pointerEvents={isResultTypeRequired ? 'none' : 'auto'}
                    mt={usesMidTermResult ? 4 : 0}
                  >
                    <VStack
                      spacing={4}
                      as={'form'}
                      bg={resultModeBg}
                      borderColor={resultModeBorder}
                      borderWidth={1}
                      borderRadius={'md'}
                      p={4}
                      align={'stretch'}
                      onSubmit={preventNativeSubmit(submit)}
                    >
                      <RecordingFormHeader
                        label={visibleResultModeLabel}
                        badgeLabel={
                          hasSelectedResultMode
                            ? resultModeLabel
                            : 'Selection required'
                        }
                        colorScheme={
                          hasSelectedResultMode ? resultModeScheme : 'red'
                        }
                      />
                      {isSelectionLoading && <SelectionLoadingNotice />}
                      <SessionAndTermFields
                        form={webForm}
                        lockTermSession={lockTermSession}
                        isDisabled={isFormDisabled}
                        onAcademicSessionChange={(value) => {
                          if (isFormDisabled) return;
                          webForm.setValue('academic_session_id', value as any);
                          reloadWithSelection({ academic_session_id: value });
                        }}
                        onTermChange={(value) => {
                          if (isFormDisabled) return;
                          webForm.setValue('term', value as any);
                          reloadWithSelection({ term: value });
                        }}
                      />
                      <ResultEntryFields
                        form={webForm}
                        courseTeacher={courseTeacher}
                        assessments={selectedAssessments}
                        assessmentValue={assessmentValue}
                        shouldShowExamInput={shouldShowExamInput}
                        isDisabled={isFormDisabled}
                        onStudentChange={(student) => {
                          if (isFormDisabled) return;
                          webForm.setValue('result', {
                            ...webForm.data.result,
                            student_id: student,
                          });
                          reloadWithSelection({
                            student_id: student?.value ?? null,
                          });
                        }}
                        onAssessmentChange={updateAssessmentValue}
                      />
                    </VStack>
                  </Box>
                </>
              )}
            </SlabBody>
          </Slab>
        </CenteredBox>
        {courseResults && (
          <>
            <Spacer height={4} />
            <ListTeachersCourseResults
              courseResults={courseResults}
              isDisabled={isSelectionLoading}
            />
          </>
        )}
      </Div>
    </DashboardLayout>
  );
}

function SelectionLoadingNotice() {
  return (
    <HStack
      bg={'brand.50'}
      borderColor={'brand.200'}
      borderWidth={1}
      borderRadius={'md'}
      color={'brand.700'}
      px={3}
      py={2}
      spacing={3}
    >
      <Spinner size="sm" color="brand.500" />
      <Text fontSize={'sm'} fontWeight={'medium'}>
        Loading the selected result details. Inputs are locked until the page
        finishes loading.
      </Text>
    </HStack>
  );
}

function RecordingFormHeader({
  label,
  badgeLabel,
  colorScheme,
}: {
  label: string;
  badgeLabel: string;
  colorScheme: string;
}) {
  return (
    <HStack justify={'space-between'} align={'center'} w={'full'}>
      <Text fontWeight={'semibold'} color={'gray.700'}>
        Recording {label}
      </Text>
      <Badge colorScheme={colorScheme}>{badgeLabel}</Badge>
    </HStack>
  );
}

function SessionAndTermFields({
  form,
  lockTermSession,
  isDisabled,
  onAcademicSessionChange,
  onTermChange,
}: {
  form: any;
  lockTermSession: boolean;
  isDisabled: boolean;
  onAcademicSessionChange: (value: number | null) => void;
  onTermChange: (value: TermType | null) => void;
}) {
  return (
    <>
      <FormControlBox
        form={form as any}
        title="Academic Session"
        formKey="academic_session_id"
      >
        <AcademicSessionSelect
          selectValue={form.data.academic_session_id}
          isMulti={false}
          isClearable={true}
          onChange={(e: any) => onAcademicSessionChange(e?.value ?? null)}
          required
          isDisabled={lockTermSession || isDisabled}
        />
      </FormControlBox>
      <FormControlBox form={form as any} title="Term" formKey="term">
        <EnumSelect
          enumData={TermType}
          selectValue={form.data.term}
          isMulti={false}
          isClearable={true}
          onChange={(e: any) => onTermChange(e?.value ?? null)}
          required
          isDisabled={lockTermSession || isDisabled}
        />
      </FormControlBox>
    </>
  );
}

function ResultEntryFields({
  form,
  courseTeacher,
  assessments,
  assessmentValue,
  shouldShowExamInput,
  isDisabled,
  onStudentChange,
  onAssessmentChange,
}: {
  form: any;
  courseTeacher: CourseTeacher;
  assessments: Assessment[];
  assessmentValue: AssessmentValue;
  shouldShowExamInput: boolean;
  isDisabled: boolean;
  onStudentChange: (student: Nullable<SelectOptionType<number>>) => void;
  onAssessmentChange: (resultKey: string, value: string) => void;
}) {
  return (
    <>
      <FormControlBox
        form={form as any}
        title="Student"
        formKey="result.student_id"
      >
        <StudentSelect
          value={form.data.result.student_id}
          isMulti={false}
          isClearable={true}
          classification={courseTeacher.classification_id}
          onChange={(e: any) => onStudentChange(e ?? null)}
          required
          isDisabled={isDisabled}
        />
      </FormControlBox>
      {assessments.map((assessment) => (
        <FormControl key={assessment.result_key + form.data.term}>
          <FormLabel>{startCase(assessment.raw_title)}</FormLabel>
          <Input
            disabled={isDisabled || assessment.depends_on !== null}
            value={
              assessmentValue[assessment.result_key] ??
              assessmentValue[assessment.raw_title] ??
              ''
            }
            onChange={(e) =>
              onAssessmentChange(assessment.result_key, e.currentTarget.value)
            }
          />
        </FormControl>
      ))}
      {shouldShowExamInput && (
        <FormControlBox form={form as any} formKey="result.exam" title="Exam">
          <Input
            disabled={isDisabled}
            value={form.data.result.exam}
            onChange={(e) =>
              form.setValue('result', {
                ...form.data.result,
                exam: e.currentTarget.value,
              })
            }
          />
        </FormControlBox>
      )}
      <FormControl>
        <FormButton isLoading={form.processing} isDisabled={isDisabled} />
      </FormControl>
    </>
  );
}

function ListTeachersCourseResults({
  courseResults,
  isDisabled,
}: {
  courseResults: PaginationResponse<CourseResult>;
  isDisabled: boolean;
}) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  async function deleteItem(obj: CourseResult) {
    if (isDisabled) return;

    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('course-results.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['courseResults'] });
  }

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
      label: 'Classification',
      value: 'classification.title',
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
    {
      label: 'Action',
      render: (row: CourseResult) => (
        <HStack>
          <IconButton
            as={InertiaLink}
            aria-label={'Edit'}
            icon={<Icon as={PencilIcon} />}
            variant={'ghost'}
            colorScheme={'brand'}
            href={instRoute('course-results.edit', [row.id])}
            isDisabled={isDisabled}
            onClick={(e) => {
              if (!isDisabled) return;
              e.preventDefault();
            }}
          />
          <DestructivePopover
            label={`Delete ${row.course?.title} result for ${
              row.student?.user!.full_name
            }?`}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
              isDisabled={isDisabled}
            />
          </DestructivePopover>
        </HStack>
      ),
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
