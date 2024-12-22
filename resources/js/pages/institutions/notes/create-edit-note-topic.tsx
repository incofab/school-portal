import React, { useState } from 'react';
import { FormControl, VStack, Checkbox } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { CourseTeacher, NoteTopic } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Editor } from '@tinymce/tinymce-react';
import { Input } from '@chakra-ui/react';
import useIsAdmin from '@/hooks/use-is-admin';
import TeacherSubjectSelect from '@/components/selectors/teacher-subject-select';
import CourseTeacherSelect from '@/components/selectors/course-teacher-select';
import { NoteStatusType, SelectOptionType } from '@/types/types';
import { SingleValue } from 'react-select';

interface Props {
  noteTopic?: NoteTopic;
  teacherCourses: CourseTeacher[];
}

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

export default function CreateOrUpdateEvent({
  noteTopic,
  teacherCourses,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [courseTeacher, setCourseTeacher] = useState(
    (noteTopic
      ? {
          label: `${noteTopic.course_teacher?.user?.first_name} ${noteTopic.course_teacher?.user?.last_name} - ${noteTopic.course?.title} - ${noteTopic.classification?.title}`,
          value: noteTopic.course_teacher?.id,
        }
      : {}) as SingleValue<SelectOptionType<number>>
  );
  const isAdmin = useIsAdmin();

  const webForm = useWebForm({
    course_teacher_id: '',
    title: noteTopic ? noteTopic.title : '',
    content: noteTopic ? noteTopic.content : '',
    is_published: noteTopic
      ? noteTopic.status === NoteStatusType.Published
        ? true
        : false
      : true,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      data.course_teacher_id = courseTeacher?.value + '';

      return noteTopic
        ? web.put(instRoute('note-topics.update', [noteTopic]), data)
        : web.post(instRoute('note-topics.store'), data);
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('note-topics.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${noteTopic ? 'Update' : 'Create'} Note Topic`}
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
                title="Topic Title"
                formKey="title"
                isRequired
              >
                <Input
                  type="text"
                  onChange={(e) =>
                    webForm.setValue('title', e.currentTarget.value)
                  }
                  value={webForm.data.title}
                />
              </FormControlBox>

              <FormControlBox
                title="Note Content"
                form={webForm as any}
                formKey="content"
                isRequired
              >
                <Editor
                  // onInit={(evt, editor) => (editorRef.current = editor)}
                  apiKey={tinymceApiKey}
                  initialValue={`${
                    noteTopic
                      ? noteTopic.content
                      : '<p>..Type the Note Content..</p>'
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
                <Checkbox
                  isChecked={webForm.data.is_published}
                  onChange={(e) =>
                    webForm.setData({
                      ...webForm.data,
                      is_published: e.currentTarget.checked,
                    })
                  }
                  size={'md'}
                  colorScheme="brand"
                >
                  Publish Instantly.
                </Checkbox>
              </FormControl>

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
