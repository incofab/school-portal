import React, { useState } from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Assignment, Classification, CourseTeacher } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Editor } from '@tinymce/tinymce-react';
import format from 'date-fns/format';
import { dateTimeFormat } from '@/util/util';
import { Input } from '@chakra-ui/react';
import useIsAdmin from '@/hooks/use-is-admin';
import TeacherSubjectSelect from '@/components/selectors/teacher-subject-select';
import CourseTeacherSelect from '@/components/selectors/course-teacher-select';
import { SelectOptionType, Nullable } from '@/types/types';
import { MultiValue, SingleValue } from 'react-select';
import ClassificationSelect from '@/components/selectors/classification-select';
import CourseSelect from '@/components/selectors/course-select';
import useSharedProps from '@/hooks/use-shared-props';
import TinyMceEditor from '@/components/tinymce-editor';

// TODO :: When Assignment is available (EDIT), how do I display the classification_ids into the 'Select Class / Classes'.
interface Props {
  assignment?: Assignment;
}

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

export default function CreateOrUpdateEvent({ assignment }: Props) {
  const isAdmin = useIsAdmin();
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentInstitutionUser } = useSharedProps();

  const webForm = useWebForm({
    course_id: assignment ? assignment.course_id : '',
    classification_ids: assignment?.classifications?.map((item) => ({
      label: item.title,
      value: item.id,
    })) as Nullable<MultiValue<SelectOptionType<number>>>,
    max_score: assignment ? assignment.max_score : '',
    expires_at: assignment ? assignment.expires_at : '',
    content: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const requestData = {
        ...data,
        institution_user_id: currentInstitutionUser.id,
        classification_ids: data.classification_ids?.map((item) => item.value),
      };

      return assignment
        ? web.put(instRoute('assignments.update', [assignment.id]), requestData)
        : web.post(instRoute('assignments.store'), requestData);
    });

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.visit(instRoute('assignments.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${assignment ? 'Update' : 'Create'} Assignment`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                form={webForm as any}
                title="Select Subject"
                formKey="course"
              >
                <CourseSelect
                  selectValue={webForm.data.course_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('course_id', e?.value)}
                  required
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Select Class(es)"
                formKey="classification"
              >
                <ClassificationSelect
                  value={webForm.data.classification_ids}
                  selectValue={webForm.data.classification_ids}
                  isMulti={true}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('classification_ids', e)
                  }
                  required
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Maximum Score"
                formKey="score"
                isRequired
              >
                <Input
                  type="number"
                  onChange={(e) =>
                    webForm.setValue('max_score', e.currentTarget.value)
                  }
                  value={webForm.data.max_score}
                />
              </FormControlBox>

              <FormControlBox
                title="Submission Deadline"
                form={webForm as any}
                formKey="expires_at"
                isRequired
              >
                <Input
                  type={'datetime-local'}
                  max={'9999-12-31'}
                  value={
                    webForm.data.expires_at
                      ? format(
                          new Date(webForm.data.expires_at),
                          dateTimeFormat
                        )
                      : ''
                  }
                  onChange={(e) =>
                    webForm.setValue(
                      'expires_at',
                      format(new Date(e.currentTarget.value), dateTimeFormat)
                    )
                  }
                />
              </FormControlBox>

              <FormControlBox
                title="Questions"
                form={webForm as any}
                formKey="content"
                isRequired
              >
                <TinyMceEditor
                  initialValue={assignment?.content}
                  value={webForm.data.content}
                  onEditorChange={(content: string) =>
                    webForm.setValue('content', content)
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
    </DashboardLayout>
  );
}
