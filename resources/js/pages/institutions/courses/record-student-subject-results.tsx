import React, { useState } from 'react';
import {
  Badge,
  Box,
  Card,
  CardBody,
  FormControl,
  FormLabel,
  HStack,
  Input,
  Radio,
  RadioGroup,
  Spacer,
  Text,
  VStack,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import {
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
import { Nullable, SelectOptionType, TermType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import FormControlBox from '@/components/forms/form-control-box';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import EnumSelect from '@/components/dropdown-select/enum-select';
import StudentSelect from '@/components/selectors/student-select';
import { Inertia } from '@inertiajs/inertia';
import {
  getResultTotalScore,
  hasResultScoreChanged,
  removeExamFromResultEntry,
  validateResultScore,
} from '@/util/result-recording-util';

type AssessmentValue = { [key: string]: string | number };
type ResultMode = 'full-term' | 'mid-term' | '';

interface CourseTeacherWithResults extends CourseTeacher {
  course_results?: CourseResult[];
}

interface SubjectResultEntry {
  course_teacher_id: number;
  ass: AssessmentValue;
  exam?: string | number;
}

interface Props {
  selectedStudent?: Student;
  courseTeachers: CourseTeacherWithResults[];
  academic_session_id?: number;
  term?: TermType;
  for_mid_term?: boolean | null;
  assessmentGroups: {
    fullTerm: Assessment[];
    midTerm: Assessment[];
  };
  showExamInput?: {
    fullTerm: boolean;
    midTerm: boolean;
  };
}

function getSelectedStudentOption(
  selectedStudent?: Student
): Nullable<SelectOptionType<number>> {
  if (!selectedStudent) {
    return null;
  }

  return {
    label: selectedStudent.user?.full_name ?? '',
    value: selectedStudent.id,
  };
}

function getResultModeValue(
  hasSelectedResultMode: boolean,
  isMidTermSelected: boolean
): ResultMode {
  if (!hasSelectedResultMode) return '';

  return isMidTermSelected ? 'mid-term' : 'full-term';
}

function selectedExistingResult(
  courseTeacher: CourseTeacherWithResults,
  isMidTermSelected: boolean
): CourseResult | undefined {
  return courseTeacher.course_results?.find(
    (item) => Boolean(item.for_mid_term) === isMidTermSelected
  );
}

export default function RecordStudentSubjectResults({
  selectedStudent,
  courseTeachers,
  academic_session_id: academicSessionId,
  term,
  for_mid_term: forMidTerm,
  assessmentGroups,
  showExamInput = { fullTerm: true, midTerm: true },
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const {
    currentAcademicSessionId,
    currentTerm,
    usesMidTermResult,
    lockTermSession,
  } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const [isSelectionLoading, setIsSelectionLoading] = useState(false);
  const hasSelectedResultMode =
    !usesMidTermResult || typeof forMidTerm === 'boolean';
  const initialResults = buildInitialResults(
    courseTeachers,
    Boolean(forMidTerm)
  );

  const webForm = useWebForm({
    academic_session_id: academicSessionId ?? currentAcademicSessionId,
    term: term ?? currentTerm,
    for_mid_term: forMidTerm ?? false,
    student_id: getSelectedStudentOption(selectedStudent),
    result: initialResults,
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
  const resultModeBg = !hasSelectedResultMode
    ? 'brand.50'
    : isMidTermSelected
    ? 'yellow.50'
    : 'blue.50';
  const resultModeBorder = !hasSelectedResultMode
    ? 'brand.200'
    : isMidTermSelected
    ? 'yellow.200'
    : 'blue.200';

  function reloadWithSelection(changes: {
    academic_session_id?: number | string | null;
    term?: string | null;
    for_mid_term?: boolean | null;
    student_id?: number | string | null;
  }) {
    const nextUrl = new URL(
      instRoute('record-student-subject-results.create'),
      window.location.origin
    );
    const params = {
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
          ? webForm.data.student_id?.value
          : changes.student_id,
    };

    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '') return;
      nextUrl.searchParams.set(
        key,
        key === 'for_mid_term' ? (value ? '1' : '0') : String(value)
      );
    });

    setIsSelectionLoading(true);
    Inertia.visit(nextUrl.toString(), {
      preserveScroll: true,
      preserveState: false,
      onFinish: () => setIsSelectionLoading(false),
    });
  }

  function setSubjectResult(
    courseTeacherId: number,
    result: SubjectResultEntry
  ) {
    webForm.setValue('result', {
      ...webForm.data.result,
      [courseTeacherId]: result,
    });
  }

  function getSubjectResult(courseTeacher: CourseTeacherWithResults) {
    return (
      webForm.data.result[courseTeacher.id] ?? {
        course_teacher_id: courseTeacher.id,
        ass: {},
        exam: '',
      }
    );
  }

  function isValidScore(score: number | string, maxScore?: number) {
    const validation = validateResultScore(score, maxScore);

    if (!validation.ok) {
      toastError(validation.message);
      return false;
    }

    return true;
  }

  const submit = async () => {
    if (!hasSelectedResultMode) {
      toastError('Select full term or mid-term recording before continuing.');
      return;
    }

    const result = Object.entries(webForm.data.result)
      .filter(([courseTeacherId, item]) =>
        hasResultScoreChanged(
          item,
          initialResults[Number(courseTeacherId)],
          shouldShowExamInput
        )
      )
      .map(([, item]) =>
        shouldShowExamInput ? item : removeExamFromResultEntry(item)
      );

    if (!webForm.data.student_id?.value || result.length < 1) {
      Inertia.visit(instRoute('course-results.index'));
      return;
    }

    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('record-student-subject-results.store'), {
        ...data,
        student_id: data.student_id?.value,
        result,
      });
    });

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('course-results.index'));
  };

  return (
    <DashboardLayout>
      <Div>
        <CenteredBox>
          <Slab>
            <SlabHeading title="Record Student Subject Results" />
            <SlabBody>
              <VStack align={'stretch'} spacing={4}>
                <Box
                  bg={resultModeBg}
                  borderColor={resultModeBorder}
                  borderWidth={1}
                  borderRadius={'md'}
                  p={4}
                >
                  <HStack justify={'space-between'} align={'center'} mb={3}>
                    <Text fontWeight={'semibold'} color={'gray.700'}>
                      Recording {visibleResultModeLabel}
                    </Text>
                    <Badge
                      colorScheme={
                        hasSelectedResultMode ? resultModeScheme : 'brand'
                      }
                    >
                      {hasSelectedResultMode ? resultModeLabel : 'Required'}
                    </Badge>
                  </HStack>
                  <SessionAndStudentFields
                    form={webForm}
                    lockTermSession={lockTermSession}
                    usesMidTermResult={usesMidTermResult}
                    hasSelectedResultMode={hasSelectedResultMode}
                    isMidTermSelected={isMidTermSelected}
                    isDisabled={isSelectionLoading}
                    onAcademicSessionChange={(value) => {
                      webForm.setValue('academic_session_id', value as any);
                      reloadWithSelection({ academic_session_id: value });
                    }}
                    onTermChange={(value) => {
                      webForm.setValue('term', value as any);
                      reloadWithSelection({ term: value });
                    }}
                    onResultModeChange={(value) => {
                      const nextForMidTerm = value === 'mid-term';
                      webForm.setValue('for_mid_term', nextForMidTerm);
                      webForm.setValue('result', {});
                      reloadWithSelection({
                        for_mid_term: nextForMidTerm,
                      });
                    }}
                    onStudentChange={(student) => {
                      webForm.setValue('student_id', student);
                      webForm.setValue('result', {});
                      reloadWithSelection({
                        student_id: student?.value ?? null,
                      });
                    }}
                  />
                </Box>
              </VStack>
            </SlabBody>
          </Slab>

          {hasSelectedResultMode && selectedStudent && (
            <>
              <Spacer height={3} />
              {courseTeachers.map((courseTeacher) => {
                const result = getSubjectResult(courseTeacher);
                const totalScore = getResultTotalScore(
                  result,
                  shouldShowExamInput
                );

                return (
                  <Card key={courseTeacher.id} mt={2} bg={'white'}>
                    <CardBody>
                      <HStack align={'stretch'}>
                        <Box>
                          <Text fontWeight={'semibold'}>
                            {courseTeacher.course?.title}
                          </Text>
                          <Text color={'gray.600'} fontSize={'sm'}>
                            {courseTeacher.user?.full_name}
                          </Text>
                        </Box>
                        <Spacer />
                        <Text
                          color={'brand.700'}
                          fontSize={'sm'}
                          display={totalScore ? undefined : 'none'}
                        >
                          Total {totalScore}
                        </Text>
                      </HStack>
                      <Wrap spacing={3}>
                        {selectedAssessments.map((assessment) => (
                          <WrapItem
                            mt={2}
                            width={'120px'}
                            key={`${courseTeacher.id}-${assessment.raw_title}`}
                          >
                            <FormControl>
                              <FormLabel
                                fontWeight={'normal'}
                                m={0}
                                whiteSpace={'nowrap'}
                                textOverflow={'ellipsis'}
                                overflow={'hidden'}
                                fontSize={'sm'}
                              >
                                {startCase(assessment.raw_title)}
                              </FormLabel>
                              <Input
                                value={result.ass[assessment.raw_title] ?? ''}
                                type="number"
                                isDisabled={
                                  isSelectionLoading ||
                                  assessment.depends_on !== null
                                }
                                onChange={(e) => {
                                  if (
                                    !isValidScore(
                                      e.currentTarget.value,
                                      assessment.max
                                    )
                                  ) {
                                    return;
                                  }
                                  setSubjectResult(courseTeacher.id, {
                                    ...result,
                                    ass: {
                                      ...result.ass,
                                      [assessment.raw_title]:
                                        e.currentTarget.value,
                                    },
                                  });
                                }}
                              />
                            </FormControl>
                          </WrapItem>
                        ))}
                        {shouldShowExamInput && (
                          <WrapItem mt={2} width={'120px'}>
                            <FormControl>
                              <FormLabel
                                fontWeight={'normal'}
                                m={0}
                                whiteSpace={'nowrap'}
                                textOverflow={'ellipsis'}
                                overflow={'hidden'}
                                fontSize={'sm'}
                              >
                                Exam
                              </FormLabel>
                              <Input
                                value={result.exam ?? ''}
                                type="number"
                                isDisabled={isSelectionLoading}
                                onChange={(e) => {
                                  if (
                                    !isValidScore(
                                      e.currentTarget.value,
                                      100 -
                                        (Number(totalScore) -
                                          Number(result.exam ?? 0))
                                    )
                                  ) {
                                    return;
                                  }
                                  setSubjectResult(courseTeacher.id, {
                                    ...result,
                                    exam: e.currentTarget.value,
                                  });
                                }}
                              />
                            </FormControl>
                          </WrapItem>
                        )}
                      </Wrap>
                    </CardBody>
                  </Card>
                );
              })}

              <FormControl mt={3}>
                <FormButton
                  isLoading={webForm.processing || isSelectionLoading}
                  onClick={submit}
                />
              </FormControl>
            </>
          )}
        </CenteredBox>
      </Div>
    </DashboardLayout>
  );
}

function buildInitialResults(
  courseTeachers: CourseTeacherWithResults[],
  isMidTermSelected: boolean
) {
  return courseTeachers.reduce((result, courseTeacher) => {
    const existingResult = selectedExistingResult(
      courseTeacher,
      isMidTermSelected
    );
    result[courseTeacher.id] = {
      course_teacher_id: courseTeacher.id,
      ass: existingResult?.assessment_values ?? {},
      exam: existingResult?.exam ?? '',
    };
    return result;
  }, {} as { [courseTeacherId: number]: SubjectResultEntry });
}

function SessionAndStudentFields({
  form,
  lockTermSession,
  usesMidTermResult,
  hasSelectedResultMode,
  isMidTermSelected,
  isDisabled,
  onAcademicSessionChange,
  onTermChange,
  onResultModeChange,
  onStudentChange,
}: {
  form: any;
  lockTermSession: boolean;
  usesMidTermResult: boolean;
  hasSelectedResultMode: boolean;
  isMidTermSelected: boolean;
  isDisabled: boolean;
  onAcademicSessionChange: (value: number | null) => void;
  onTermChange: (value: TermType | null) => void;
  onResultModeChange: (value: ResultMode) => void;
  onStudentChange: (student: Nullable<SelectOptionType<number>>) => void;
}) {
  return (
    <VStack align={'stretch'} spacing={3}>
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
      {usesMidTermResult && (
        <FormControlBox
          form={form as any}
          formKey="for_mid_term"
          title="Result Type"
        >
          <RadioGroup
            value={getResultModeValue(hasSelectedResultMode, isMidTermSelected)}
            onChange={(nextValue) =>
              onResultModeChange(nextValue as ResultMode)
            }
            isDisabled={isDisabled}
          >
            <HStack spacing={6}>
              <Radio value="full-term">Full term</Radio>
              <Radio value="mid-term">Mid-term</Radio>
            </HStack>
          </RadioGroup>
        </FormControlBox>
      )}
      <FormControlBox form={form as any} title="Student" formKey="student_id">
        <StudentSelect
          value={form.data.student_id}
          isMulti={false}
          isClearable={true}
          onChange={(e: any) => onStudentChange(e ?? null)}
          required
          isDisabled={isDisabled || !hasSelectedResultMode}
        />
      </FormControlBox>
    </VStack>
  );
}
