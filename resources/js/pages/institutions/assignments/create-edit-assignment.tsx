import React, { useState } from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Assignment, CourseTeacher } from '@/types/models';
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
import { SelectOptionType } from '@/types/types';
import { SingleValue } from 'react-select';

interface Props {
  assignment?: Assignment;
  teacherCourses: CourseTeacher[];
}

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

export default function CreateOrUpdateEvent({
  assignment,
  teacherCourses,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [courseTeacher, setCourseTeacher] = useState(
    (assignment
      ? {
          label: `${assignment.course_teacher?.user?.first_name} ${assignment.course_teacher?.user?.last_name} - ${assignment.course?.title} - ${assignment.classification?.title}`,
          value: assignment.course_teacher?.id,
        }
      : {}) as SingleValue<SelectOptionType<number>>
  );
  const isAdmin = useIsAdmin();

  const webForm = useWebForm({
    course_teacher_id: '',
    max_score: assignment ? assignment.max_score : '',
    expires_at: assignment ? assignment.expires_at : '',
    content: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      data.course_teacher_id = courseTeacher?.value + '';

      return assignment
        ? web.put(instRoute('assignments.update', [assignment]), data)
        : web.post(instRoute('assignments.store'), data);
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
              {teacherCourses.length > 0 && (
                <FormControlBox
                  title="Select Subject"
                  form={webForm as any}
                  formKey="course_teacher_id"
                  isRequired
                >
                  <TeacherSubjectSelect
                    selectValue={courseTeacher?.value}
                    teacherCourses={teacherCourses ?? []}
                    onChange={(e: any) => setCourseTeacher(e)}
                  />
                </FormControlBox>
              )}
              {isAdmin && (
                <FormControlBox
                  title="Select the subject teacher"
                  form={webForm as any}
                  formKey="course_teacher_id"
                  isRequired
                >
                  <CourseTeacherSelect
                    value={courseTeacher}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e) => setCourseTeacher(e)}
                  />
                </FormControlBox>
              )}

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
                <Editor
                  // onInit={(evt, editor) => (editorRef.current = editor)}
                  apiKey={tinymceApiKey}
                  initialValue={`${
                    assignment
                      ? assignment.content
                      : '<p>..Type the Question..</p>'
                  } `}
                  init={{
                    height: 300,
                    menubar: true,
                    plugins: [
                      'advlist autolink lists link image charmap print preview anchor',
                      'searchreplace visualblocks code fullscreen',
                      'insertdatetime media table paste code help wordcount',
                    ],
                    toolbar:
                      'undo redo | formatselect | bold italic backcolor | alignleft aligncenter  alignright alignjustify | bullist numlist outdent indent |  removeformat',
                    content_style:
                      'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                  }}
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
