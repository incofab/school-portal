import React from 'react';
import {
  Box,
  Button,
  Checkbox,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  SimpleGrid,
  Text,
  VStack,
} from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import { Assessment, Event, EventCourseable } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import EnumSelect from '@/components/dropdown-select/enum-select';
import FormControlBox from '@/components/forms/form-control-box';
import CourseTeacherSelect from '@/components/selectors/course-teacher-select';
import MySelect from '@/components/dropdown-select/my-select';
import useSharedProps from '@/hooks/use-shared-props';
import { Nullable, SelectOptionType, TermType } from '@/types/types';
import { LinkButton } from '@/components/buttons';

interface Props {
  event: Event;
  assessments: Assessment[];
}

interface EventCourseableTransfer {
  event_courseable_id: number;
  course_teacher_id: Nullable<SelectOptionType<number>>;
  assessment_id: number | '';
  for_mid_term: boolean;
  for_exam: boolean;
}

interface TransferFormData {
  academic_session_id: number;
  term: string;
  event_courseables: EventCourseableTransfer[];
}

export default function TransferEventResultsMultiple({
  event,
  assessments,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const {
    currentAcademicSessionId,
    currentTerm,
    usesMidTermResult,
    lockTermSession,
  } = useSharedProps();
  const eventCourseables = event.event_courseables ?? [];

  const webForm = useWebForm<TransferFormData>(() => ({
    academic_session_id: currentAcademicSessionId,
    term: currentTerm,
    event_courseables: eventCourseables.map((eventCourseable) => ({
      event_courseable_id: eventCourseable.id,
      course_teacher_id: null,
      assessment_id: '',
      for_mid_term: false,
      for_exam: false,
    })),
  }));

  const errorFor = (index: number, field: string) =>
    (webForm.errors as Record<string, string>)[
      `event_courseables.${index}.${field}`
    ];

  const updateCourseable = (
    index: number,
    update: Partial<EventCourseableTransfer>
  ) => {
    const updated = [...webForm.data.event_courseables];
    updated[index] = { ...updated[index], ...update };
    webForm.setValue('event_courseables', updated);
  };

  const getCourseTitle = (eventCourseable: EventCourseable) => {
    const courseTitle = eventCourseable.courseable?.course?.title ?? 'Course';
    const sessionTitle = eventCourseable.courseable?.session
      ? ` - ${eventCourseable.courseable.session}`
      : '';
    return `${courseTitle}${sessionTitle}`;
  };

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('events.transfer-results-multiple.store', [event]),
        {
          academic_session_id: data.academic_session_id,
          term: data.term,
          event_courseables: data.event_courseables.map((item) => ({
            event_courseable_id: item.event_courseable_id,
            course_teacher_id: item.course_teacher_id?.value,
            assessment_id: item.for_exam ? null : item.assessment_id || null,
            for_mid_term: item.for_mid_term,
          })),
        }
      );
    });

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('events.index'));
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`Transfer ${event.title} Results`}
          rightElement={
            <LinkButton
              href={instRoute('events.index')}
              variant="link"
              title="Back"
            />
          }
        />
        <SlabBody>
          <VStack align="stretch" spacing={6}>
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
                isDisabled={lockTermSession}
                required
              />
            </FormControlBox>
            <FormControlBox form={webForm as any} title="Term" formKey="term">
              <EnumSelect
                enumData={TermType}
                selectValue={webForm.data.term}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('term', e?.value)}
                isDisabled={lockTermSession}
                required
              />
            </FormControlBox>

            <VStack align="stretch" spacing={4}>
              {eventCourseables.map((eventCourseable, index) => {
                const transferData = webForm.data.event_courseables[index];
                return (
                  <Box
                    key={eventCourseable.id}
                    borderWidth="1px"
                    borderRadius="md"
                    p={4}
                  >
                    <Text fontWeight="semibold" mb={3}>
                      {getCourseTitle(eventCourseable)}
                    </Text>
                    <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                      <FormControl
                        isRequired
                        isInvalid={!!errorFor(index, 'course_teacher_id')}
                      >
                        <FormLabel>Teacher</FormLabel>
                        <CourseTeacherSelect
                          course={eventCourseable.courseable?.course_id}
                          classification={event.classification_id}
                          value={transferData.course_teacher_id}
                          isMulti={false}
                          isClearable={true}
                          onChange={(value) =>
                            updateCourseable(index, {
                              course_teacher_id: value,
                            })
                          }
                          required
                        />
                        <FormErrorMessage>
                          {errorFor(index, 'course_teacher_id')}
                        </FormErrorMessage>
                      </FormControl>

                      <FormControl
                        isInvalid={!!errorFor(index, 'assessment_id')}
                        isDisabled={transferData.for_exam}
                      >
                        <FormLabel>Assessment</FormLabel>
                        <MySelect
                          isMulti={false}
                          selectValue={transferData.assessment_id}
                          getOptions={() =>
                            assessments.map((assessment) => ({
                              label: assessment.title,
                              value: assessment.id,
                            }))
                          }
                          onChange={(e: any) =>
                            updateCourseable(index, {
                              assessment_id: e.value,
                              for_exam: false,
                            })
                          }
                          isDisabled={transferData.for_exam}
                        />
                        <FormErrorMessage>
                          {errorFor(index, 'assessment_id')}
                        </FormErrorMessage>
                      </FormControl>
                    </SimpleGrid>
                    <HStack mt={4} spacing={6} align="center">
                      {usesMidTermResult && (
                        <Checkbox
                          isChecked={transferData.for_mid_term}
                          onChange={(e) =>
                            updateCourseable(index, {
                              for_mid_term: e.currentTarget.checked,
                            })
                          }
                        >
                          For Mid-Term Result
                        </Checkbox>
                      )}
                      <Checkbox
                        isChecked={transferData.for_exam}
                        onChange={(e) => {
                          const forExam = e.currentTarget.checked;
                          updateCourseable(index, {
                            for_exam: forExam,
                            assessment_id: '',
                          });
                        }}
                      >
                        Transfer to Exam scores
                      </Checkbox>
                    </HStack>
                  </Box>
                );
              })}
            </VStack>

            <HStack spacing={2} justify="flex-end">
              <Button
                variant="ghost"
                onClick={() => Inertia.visit(instRoute('events.index'))}
              >
                Cancel
              </Button>
              <Button
                colorScheme="brand"
                onClick={onSubmit}
                isLoading={webForm.processing}
              >
                Submit
              </Button>
            </HStack>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
