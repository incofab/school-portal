import React from 'react';
import {
  Card,
  CardBody,
  FormControl,
  FormLabel,
  Input,
  Spacer,
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
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SelectOptionType } from '@/types/types';
import Dt from '@/components/dt';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';

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
}

export default function RecordClassCourseResult({
  courseTeacher,
  students,
  assessments,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { currentAcademicSession, currentTerm, currentlyOnMidTerm } =
    useSharedProps();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    academic_session_id: currentAcademicSession.id,
    term: currentTerm,
    for_mid_term: currentlyOnMidTerm,
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
    ...(currentlyOnMidTerm ? [{ label: 'For Mid Term', value: 'Yes' }] : []),
  ];

  return (
    <DashboardLayout>
      <Div>
        <CenteredBox>
          <Slab>
            <SlabHeading title={`Record Class Result`} />
            <SlabBody>
              <Dt contentData={details} />
            </SlabBody>
          </Slab>
          <Spacer height={4} />
          {students.map((student) => {
            const existingResult =
              student['course_results']?.[0] ?? ({} as CourseResult);
            const result = webForm.data.result[student.id] ?? {
              ...existingResult,
              ass: existingResult?.assessment_values ?? {},
            };
            result.student_id = student.id;
            return (
              <Card key={student.id + 'exam' + webForm.data.term} mt={2}>
                <CardBody>
                  <Text display={'block'} fontWeight={'semibold'} mb={3}>
                    {student.user!.full_name}
                  </Text>
                  <Wrap spacing={3}>
                    <WrapItem mt={2}>
                      <FormControl>
                        <FormLabel fontWeight={'normal'} m={0}>
                          Exam
                        </FormLabel>
                        <Input
                          value={result.exam}
                          type="number"
                          onChange={(e) =>
                            webForm.setValue('result', {
                              ...webForm.data.result,
                              [student.id]: {
                                ...result,
                                exam: e.currentTarget.value,
                              },
                            })
                          }
                        />
                      </FormControl>
                    </WrapItem>
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
                          key={
                            student.id +
                            assessment.raw_title +
                            webForm.data.term
                          }
                        >
                          <FormControl>
                            <FormLabel fontWeight={'normal'} m={0}>
                              {startCase(assessment.raw_title)}
                            </FormLabel>
                            <Input
                              value={result['ass'][assessment.raw_title] ?? ''}
                              type="number"
                              onChange={(e) =>
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
                                })
                              }
                            />
                          </FormControl>
                        </WrapItem>
                      );
                    })}
                  </Wrap>
                </CardBody>
              </Card>
            );
          })}
          <FormControl mt={3}>
            <FormButton isLoading={webForm.processing} onClick={submit} />
          </FormControl>
        </CenteredBox>
      </Div>
    </DashboardLayout>
  );
}
