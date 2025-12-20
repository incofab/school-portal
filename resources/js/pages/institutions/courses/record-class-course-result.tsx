import React, { useState } from 'react';
import {
  Card,
  CardBody,
  Checkbox,
  FormControl,
  FormLabel,
  HStack,
  Input,
  Spacer,
  Spinner,
  Text,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import {
  Assessment,
  CourseResult,
  CourseTeacher,
  Student,
} from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { BrandButton, FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SelectOptionType } from '@/types/types';
import Dt from '@/components/dt';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import FormControlBox from '@/components/forms/form-control-box';
import MySelect from '@/components/dropdown-select/my-select';

interface ResultEntry {
  [studentId: string]: {
    ass: { [key: string]: string | number };
    exam: string;
    student_id: number;
  };
}

interface Props {
  courseTeacher: CourseTeacher;
  students: Student[];
  assessments: Assessment[];
  teachersCourses: { [id: number]: CourseTeacher };
}

export default function RecordClassCourseResult({
  courseTeacher,
  students,
  assessments,
  teachersCourses,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { currentAcademicSession, currentTerm, usesMidTermResult } =
    useSharedProps();
  const selectedCourseTeacherState = useState<CourseTeacher>(courseTeacher);
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    academic_session_id: currentAcademicSession.id,
    term: currentTerm,
    for_mid_term: false,
    result: {} as ResultEntry,
  });

  const submit = async () => {
    if (Object.keys(webForm.data.result).length < 1) {
      Inertia.visit(instRoute('course-results.index'));
      return;
    }
    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('record-class-results.store', [courseTeacher]),
        data
      );
    });

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.visit(instRoute('course-results.index'));
  };

  const details: SelectOptionType[] = [
    { label: 'Subject', value: courseTeacher.course?.title ?? '' },
    { label: 'Class', value: courseTeacher.classification?.title ?? '' },
    { label: 'Teacher', value: courseTeacher.user?.full_name ?? '' },
    { label: 'Session', value: currentAcademicSession.title },
    { label: 'Term', value: startCase(String(currentTerm)) },
    ...(usesMidTermResult ? [{ label: 'For Mid Term', value: 'Yes' }] : []),
  ];

  function getStudentTotal(result: {
    ass: { [key: string]: string | number };
    exam: string;
    student_id: number;
  }) {
    let score = 0;
    Object.entries(result.ass).forEach(([, assScore]) => {
      score += Number(assScore);
    });
    const totalScore = score + Number(result.exam);
    return isNaN(totalScore) ? '' : totalScore;
  }

  function isValidScore(score: number | string, maxScore?: number) {
    score = Number(score);
    if (isNaN(score)) {
      toastError(`Score invalid. It must be a number`);
      return false;
    }
    if (!maxScore || maxScore == 0) {
      return true;
    }
    if (score > maxScore) {
      toastError(`Score cannot be greater than ${maxScore}`);
      return false;
    }
    return true;
  }

  return (
    <DashboardLayout>
      <Div>
        <CenteredBox>
          <Slab>
            <SlabHeading title={`Record Class Result`} />
            <SlabBody>
              <SwitchCourseTeacher
                courseTeacher={courseTeacher}
                teachersCourses={teachersCourses}
                selectedCourseTeacherState={selectedCourseTeacherState}
              />
              <Dt contentData={details} />
            </SlabBody>
          </Slab>
          <Spacer height={3} />

          {usesMidTermResult && (
            <FormControlBox
              form={webForm as any}
              formKey="for_mid_term"
              title=""
            >
              <Checkbox
                isChecked={webForm.data.for_mid_term}
                onChange={(e) =>
                  webForm.setValue('for_mid_term', e.currentTarget.checked)
                }
              >
                For Mid-Term Result
              </Checkbox>
            </FormControlBox>
          )}
          <Spacer height={3} />

          {selectedCourseTeacherState[0].id === courseTeacher.id &&
            students.map((student) => {
              const existingResult =
                student['course_results']?.[0] ?? ({} as CourseResult);
              const result = webForm.data.result[student.id] ?? {
                ...existingResult,
                ass: existingResult?.assessment_values ?? {},
              };
              result.student_id = student.id;
              const studentTotalScore = getStudentTotal(result);
              return (
                <Card key={student.id + 'exam' + webForm.data.term} mt={2}>
                  <CardBody>
                    <HStack align={'stretch'}>
                      <Text display={'block'} fontWeight={'semibold'} mb={3}>
                        {student.user!.full_name}
                      </Text>
                      <Spacer />
                      <Text
                        color={'brand.700'}
                        fontSize={'sm'}
                        // fontWeight={'semibold'}
                        display={studentTotalScore ? undefined : 'none'}
                      >
                        Total {studentTotalScore}
                      </Text>
                    </HStack>
                    <Wrap spacing={3}>
                      {assessments.map((assessment) => {
                        if (
                          assessment.term &&
                          assessment.term !== webForm.data.term
                        ) {
                          return null;
                        }
                        return (
                          <WrapItem
                            mt={2}
                            width={'120px'}
                            key={
                              student.id +
                              assessment.raw_title +
                              webForm.data.term
                            }
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
                                value={
                                  result['ass'][assessment.raw_title] ?? ''
                                }
                                type="number"
                                onChange={(e) => {
                                  if (
                                    !isValidScore(
                                      e.currentTarget.value,
                                      assessment.max
                                    )
                                  ) {
                                    return;
                                  }
                                  webForm.setValue('result', {
                                    ...webForm.data.result,
                                    [student.id]: {
                                      ...result,
                                      ass: {
                                        ...result.ass,
                                        [assessment.raw_title]:
                                          e.currentTarget.value,
                                      },
                                    },
                                  });
                                }}
                              />
                            </FormControl>
                          </WrapItem>
                        );
                      })}
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
                            value={result.exam}
                            type="number"
                            onChange={(e) => {
                              if (
                                !isValidScore(
                                  e.currentTarget.value,
                                  100 -
                                    (Number(studentTotalScore) -
                                      Number(result.exam))
                                )
                              ) {
                                return;
                              }
                              webForm.setValue('result', {
                                ...webForm.data.result,
                                [student.id]: {
                                  ...result,
                                  exam: e.currentTarget.value,
                                },
                              });
                            }}
                          />
                        </FormControl>
                      </WrapItem>
                    </Wrap>
                  </CardBody>
                </Card>
              );
            })}
          <FormControl mt={3}>
            <FormButton
              isLoading={
                selectedCourseTeacherState[0].id != courseTeacher.id ||
                webForm.processing
              }
              onClick={submit}
            />
          </FormControl>
        </CenteredBox>
      </Div>
    </DashboardLayout>
  );
}

function SwitchCourseTeacher({
  courseTeacher,
  teachersCourses,
  selectedCourseTeacherState,
}: {
  teachersCourses: { [id: number]: CourseTeacher };
  courseTeacher: CourseTeacher;
  selectedCourseTeacherState: [
    CourseTeacher,
    React.Dispatch<React.SetStateAction<CourseTeacher>>
  ];
}) {
  const { instRoute } = useInstitutionRoute();
  const [selectedCourseTeacher, setSelectedCourseTeacher] =
    selectedCourseTeacherState;
  function getValue(ct: CourseTeacher) {
    return {
      label: `${ct.classification?.title} - ${ct.course?.title}`,
      value: ct.id,
    };
  }
  return (
    <Div pt={2} pb={4}>
      <Text>Change Subject</Text>
      <HStack w={'full'} spacing={2}>
        <Div flex={1}>
          <MySelect
            isMulti={false}
            selectValue={getValue(selectedCourseTeacher)}
            getOptions={() =>
              Object.values(teachersCourses).map((ct) => getValue(ct))
            }
            onChange={(e: any) => {
              if (!e || e.value == selectedCourseTeacher.id) return;
              setSelectedCourseTeacher(teachersCourses[e.value]);
              Inertia.visit(
                instRoute('record-class-results.create', [e.value])
              );
            }}
          />
        </Div>
        {selectedCourseTeacher.id != courseTeacher.id && (
          <Spinner size="md" color="brand.500" />
        )}
      </HStack>
    </Div>
  );
}
