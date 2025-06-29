import React, { useState } from 'react';
import { Checkbox, FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { SchemeOfWork, LessonPlan, CourseTeacher } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Editor } from '@tinymce/tinymce-react';
import { SingleValue } from 'react-select';
import { SelectOptionType } from '@/types/types';
import LessonPlanCourseTeacherSelect from '@/components/selectors/lesson-plan-course-teacher-select';
import TinyMceEditor from '@/components/tinymce-editor';

interface Props {
  schemeOfWork?: SchemeOfWork;
  lessonPlan?: LessonPlan;
  lessonPlanCourseTeachers?: CourseTeacher[];
}

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

export default function CreateOrUpdateTopic({
  schemeOfWork,
  lessonPlan,
  lessonPlanCourseTeachers,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const [courseTeacher, setCourseTeacher] = useState(
    (lessonPlan
      ? {
          label: `${lessonPlan.course_teacher?.user?.full_name}`,
          value: lessonPlan.course_teacher?.id,
        }
      : {}) as SingleValue<SelectOptionType<number>>
  );

  const webForm = useWebForm({
    course_teacher_id: '',
    scheme_of_work_id: schemeOfWork ? schemeOfWork.id : null,
    objective: lessonPlan ? lessonPlan.objective : '',
    activities: lessonPlan ? lessonPlan.activities : '',
    content: lessonPlan ? lessonPlan.content : '',

    is_used_by_institution_group: lessonPlan
      ? lessonPlan.institution_group_id !== null
        ? true
        : false
      : false,
  });

  const topicId = lessonPlan
    ? lessonPlan.scheme_of_work?.topic_id
    : schemeOfWork?.topic_id;

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      data.course_teacher_id = String(courseTeacher?.value);
      return web.post(
        instRoute('lesson-plans.store-or-update', lessonPlan ?? [lessonPlan]),
        data
      );

      /*
      return lessonPlan
        ? web.put(instRoute('lesson-plans.update', [lessonPlan]), data)
        : web.post(instRoute('lesson-plans.store'), data);
        */
    });

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('inst-topics.show', [topicId]));
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`${lessonPlan ? 'Update' : 'Create'} Lesson Plan`}
        />
        <SlabBody>
          <VStack
            spacing={4}
            as={'form'}
            onSubmit={preventNativeSubmit(submit)}
          >
            <FormControlBox
              title="Select the subject teacher"
              form={webForm as any}
              formKey="course_teacher_id"
              isRequired
            >
              <LessonPlanCourseTeacherSelect
                selectValue={courseTeacher?.value}
                lessonPlanCourseTeachers={lessonPlanCourseTeachers ?? []}
                onChange={(e: any) => setCourseTeacher(e)}
              />
            </FormControlBox>

            <FormControlBox
              title="Objective"
              form={webForm as any}
              formKey="objective"
              isRequired
            >
              <TinyMceEditor
                initialValue={lessonPlan?.objective}
                value={webForm.data.objective}
                onEditorChange={(objective: string) =>
                  webForm.setValue('objective', objective)
                }
              />
            </FormControlBox>

            <FormControlBox
              title="Activities"
              form={webForm as any}
              formKey="activities"
              isRequired
            >
              <TinyMceEditor
                initialValue={lessonPlan?.activities}
                value={webForm.data.activities}
                onEditorChange={(activities: string) =>
                  webForm.setValue('activities', activities)
                }
              />
            </FormControlBox>

            <FormControlBox
              title="Content"
              form={webForm as any}
              formKey="content"
              isRequired
            >
              <TinyMceEditor
                initialValue={lessonPlan?.content}
                value={webForm.data.content}
                onEditorChange={(content: string) =>
                  webForm.setValue('content', content)
                }
              />
            </FormControlBox>

            <FormControl>
              <Checkbox
                isChecked={webForm.data.is_used_by_institution_group}
                onChange={(e) =>
                  webForm.setData({
                    ...webForm.data,
                    is_used_by_institution_group: e.currentTarget.checked,
                  })
                }
                size={'md'}
                colorScheme="brand"
              >
                Applies to entire Institution Group.
              </Checkbox>
            </FormControl>

            <FormControl>
              <FormButton isLoading={webForm.processing} />
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
