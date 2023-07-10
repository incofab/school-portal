import React, { useState } from 'react';
import { Checkbox, Divider, Text, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { Nullable, SelectOptionType, TermType } from '@/types/types';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useSharedProps from '@/hooks/use-shared-props';
import { Assessment } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import CenteredBox from '@/components/centered-box';
import FormControlBox from '@/components/forms/form-control-box';
import ClassificationSelect from '@/components/selectors/classification-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import EnumSelect from '@/components/dropdown-select/enum-select';
import CourseTeacherSelect from '@/components/selectors/course-teacher-select';
import { preventNativeSubmit } from '@/util/util';
import { FormButton } from '@/components/buttons';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';

interface Props {
  assessment: Assessment;
}

export default function InsertAssessmentScoreFromCourseResult({
  assessment,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSession, currentTerm, usesMidTermResult } =
    useSharedProps();

  const [fromDate, setFromDate] = useState({
    academic_session_id: currentAcademicSession,
    term: currentTerm,
    classification_id: '',
    for_mid_term: false,
  });

  const [toDate, setToDate] = useState({
    academic_session_id: currentAcademicSession,
    term: currentTerm,
    classification_id: '',
    for_mid_term: false,
  });

  const webForm = useWebForm({
    // course_teacher_id: {} as Nullable<SelectOptionType<number>>,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('assessments.insert-score-from-course-result.store', [
          assessment,
        ]),
        {
          ...data,
          from: fromDate,
          to: toDate,
        }
      );
    });

    if (!handleResponseToast(res)) return;
    Inertia.visit(
      instRoute('course-results.index', {
        academicSession: toDate.academic_session_id,
        classification: toDate.classification_id,
        term: toDate.term,
        forMidTerm: toDate.for_mid_term,
      })
    );
  };

  return (
    <DashboardLayout>
      <CenteredBox as={'form'} onSubmit={preventNativeSubmit(onSubmit)}>
        <Slab>
          <SlabHeading
            title={`Inject ${assessment.title} score from existing result`}
          />
          <SlabBody>
            <Text fontSize={'md'}>Extract scores from</Text>
            <Divider />
            <VStack align={'stretch'}>
              <FormControlBox
                form={webForm as any}
                formKey="from.classification_id"
                title="Class"
              >
                <ClassificationSelect
                  value={fromDate.classification_id}
                  isMulti={false}
                  onChange={(e: any) =>
                    setFromDate({ ...fromDate, classification_id: e.value })
                  }
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                title="Academic Session"
                formKey="from.academic_session_id"
              >
                <AcademicSessionSelect
                  selectValue={fromDate.academic_session_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    setFromDate({ ...fromDate, academic_session_id: e.value })
                  }
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                title="Term"
                formKey="from.term"
              >
                <EnumSelect
                  enumData={TermType}
                  selectValue={fromDate.term}
                  isClearable={true}
                  onChange={(e: any) =>
                    setFromDate({ ...fromDate, term: e.value })
                  }
                  required
                />
              </FormControlBox>
              {usesMidTermResult && (
                <FormControlBox
                  form={webForm as any}
                  formKey="from.for_mid_term"
                  title=""
                >
                  <Checkbox
                    isChecked={fromDate.for_mid_term}
                    onChange={(e) =>
                      setFromDate({
                        ...fromDate,
                        for_mid_term: e.currentTarget.checked,
                      })
                    }
                  >
                    For Mid-Term Result
                  </Checkbox>
                </FormControlBox>
              )}
            </VStack>
            <Divider my={5} />
            <Text>Populate Scores for:</Text>
            <Divider mb={3} />
            <VStack align={'stretch'}>
              <FormControlBox
                form={webForm as any}
                formKey="to.classification_id"
                title="Class"
              >
                <ClassificationSelect
                  value={toDate.classification_id}
                  isMulti={false}
                  onChange={(e: any) =>
                    setToDate({ ...toDate, classification_id: e.value })
                  }
                  required
                />
              </FormControlBox>
              {/* <FormControlBox
                form={webForm as any}
                formKey="course_teacher_id"
                title="Course Teacher"
              >
                <CourseTeacherSelect
                  // classification={toDate.classification_id}
                  value={webForm.data.course_teacher_id}
                  isMulti={false}
                  onChange={(e: any) =>
                    webForm.setValue('course_teacher_id', e)
                  }
                  required
                />
              </FormControlBox> */}
              <FormControlBox
                form={webForm as any}
                title="Academic Session"
                formKey="to.academic_session_id"
              >
                <AcademicSessionSelect
                  selectValue={toDate.academic_session_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    setToDate({ ...toDate, academic_session_id: e.value })
                  }
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                title="Term"
                formKey="to.term"
              >
                <EnumSelect
                  enumData={TermType}
                  selectValue={toDate.term}
                  isClearable={true}
                  onChange={(e: any) => setToDate({ ...toDate, term: e.value })}
                  required
                />
              </FormControlBox>
              {usesMidTermResult && (
                <FormControlBox
                  form={webForm as any}
                  formKey="to.for_mid_term"
                  title=""
                >
                  <Checkbox
                    isChecked={toDate.for_mid_term}
                    onChange={(e) =>
                      setToDate({
                        ...toDate,
                        for_mid_term: e.currentTarget.checked,
                      })
                    }
                  >
                    For Mid-Term Result
                  </Checkbox>
                </FormControlBox>
              )}
            </VStack>
            <FormButton title="Submit" mt={4} />
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
