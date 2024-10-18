import React from 'react';
import { AxiosInstance } from 'axios';
import {
  FormControl,
  Button,
  FormErrorMessage,
  HStack,
  VStack,
  FormLabel,
  Checkbox,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import { Nullable, SelectOptionType, TermType } from '@/types/types';
import useIsAdmin from '@/hooks/use-is-admin';
import { Assessment, CourseTeacher, Event } from '@/types/models';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import CourseTeacherSelect from '../selectors/course-teacher-select';
import FormControlBox from '../forms/form-control-box';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import useSharedProps from '@/hooks/use-shared-props';
import MySelect from '../dropdown-select/my-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  event: Event;
  assessments: Assessment[];
  courseTeacher?: CourseTeacher;
}

export default function TransferEventResultModal({
  isOpen,
  onSuccess,
  onClose,
  event,
  assessments,
  courseTeacher,
}: Props) {
  const isAdmin = useIsAdmin();
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm, usesMidTermResult } =
    useSharedProps();
  const webForm = useWebForm({
    academic_session_id: currentAcademicSessionId,
    term: currentTerm,
    for_mid_term: false,
    for_exam: true,
    assessment_id: '',
    course_teacher_id: {
      label: courseTeacher?.user?.full_name,
      value: courseTeacher?.id,
    } as Nullable<SelectOptionType<number>>,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) => {
      return web.post(instRoute('events.transfer-results', [event]), {
        ...data,
        course_teacher_id: data.course_teacher_id!.value,
      });
    });

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={`Transfer ${event.title} Results`}
      bodyContent={
        <VStack>
          {isAdmin && (
            <FormControl
              isRequired
              isInvalid={!!webForm.errors.course_teacher_id}
            >
              <FormLabel>Teacher</FormLabel>
              <CourseTeacherSelect
                value={webForm.data.course_teacher_id}
                isMulti={false}
                isClearable={true}
                onChange={(e) => webForm.setValue('course_teacher_id', e)}
                required
              />
              <FormErrorMessage>
                {webForm.errors.course_teacher_id}
              </FormErrorMessage>
            </FormControl>
          )}
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
            />
          </FormControlBox>
          <FormControlBox form={webForm as any} title="Term" formKey="term">
            <EnumSelect
              enumData={TermType}
              selectValue={webForm.data.term}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('term', e?.value)}
              required
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
                  webForm.setValue('for_mid_term', e.currentTarget.checked)
                }
              >
                For Mid-Term Result
              </Checkbox>
            </FormControlBox>
          )}
          <FormControlBox form={webForm as any} formKey="for_exam" title="">
            <Checkbox
              isChecked={webForm.data.for_exam}
              onChange={(e) => {
                const forExam = e.currentTarget.checked;
                webForm.setData({
                  ...webForm.data,
                  for_exam: forExam,
                  assessment_id: '',
                });
              }}
            >
              Transfer to Exam scores
            </Checkbox>
          </FormControlBox>
          {!webForm.data.for_exam && (
            <FormControlBox form={webForm as any} title="Term" formKey="term">
              <MySelect
                isMulti={false}
                selectValue={webForm.data.assessment_id}
                getOptions={() =>
                  assessments.map((assessment, i) => ({
                    label: assessment.title,
                    value: assessment.id,
                  }))
                }
                onChange={(e: any) =>
                  webForm.setValue('assessment_id', e.value)
                }
              />
            </FormControlBox>
          )}
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme={'brand'}
            onClick={onSubmit}
            isLoading={webForm.processing}
          >
            Submit
          </Button>
        </HStack>
      }
    />
  );
}
