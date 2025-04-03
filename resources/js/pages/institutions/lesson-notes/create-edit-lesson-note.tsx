import React from 'react';
import { FormControl, VStack, Checkbox, HStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { LessonNote, LessonPlan } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { BrandButton, FormButton, LinkButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Editor } from '@tinymce/tinymce-react';
import { Input } from '@chakra-ui/react';
import { NoteStatusType } from '@/types/types';

interface Props {
  lessonPlan?: LessonPlan;
  lessonNote?: LessonNote;
}

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

export default function CreateOrUpdateEvent({ lessonPlan, lessonNote }: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    lesson_plan_id: lessonPlan ? lessonPlan.id : lessonNote?.lesson_plan?.id,
    title: lessonNote ? lessonNote.title : '',
    content: lessonNote ? lessonNote.content : '',
    is_published: lessonNote
      ? lessonNote.status === NoteStatusType.Published
        ? true
        : false
      : true,

    is_used_by_classification_group: lessonNote
      ? lessonNote.classification_group_id !== null
        ? true
        : false
      : false,

    is_used_by_institution_group: lessonNote
      ? lessonNote.institution_group_id !== null
        ? true
        : false
      : false,
  });

  const webForm_genWithAi = useWebForm({
    topic_id: 0,
    title: '',
  });

  const topicId = lessonNote
    ? lessonNote.topic_id
    : lessonPlan?.scheme_of_work?.topic_id;

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('lesson-notes.store-or-update', lessonNote ?? [lessonNote]),
        data
      );
    });

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('inst-topics.show', [topicId]));
  };

  const genNoteWithAi = async () => {
    const response = await webForm_genWithAi.submit((data, web) => {
      data.topic_id = topicId ?? 0;
      data.title = webForm.data.title;
      return web.post(instRoute('lesson-notes.gen-ai-note'), data);
    });

    webForm.setValue('content', response.data[0]);

    if (!handleResponseToast(response)) {
      return;
    }
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${lessonNote ? 'Update' : 'Create'} Lesson Note`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                form={webForm as any}
                title="Title"
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
                    lessonNote
                      ? lessonNote.content
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
                      'body { font-family:Helvetica,Arial,sans-serif; font-size:14px;}',
                  }}
                  value={webForm.data.content}
                  onEditorChange={(content: string) =>
                    webForm.setValue('content', content)
                  }
                />
              </FormControlBox>

              <FormControl>
                <HStack justifyContent={'space-between'}>
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
                  <BrandButton
                    size="xs"
                    variant={'outline'}
                    title="Generate with AI"
                    isLoading={webForm_genWithAi.processing}
                    loadingText="Processing... Please Wait!!"
                    onClick={preventNativeSubmit(() => {
                      if (!webForm.data.title) {
                        toastError('Kindly enter the title of the lesson note');
                        return;
                      }
                      genNoteWithAi();
                    })}
                  />
                </HStack>
              </FormControl>

              <FormControl>
                <Checkbox
                  isChecked={webForm.data.is_used_by_classification_group}
                  onChange={(e) =>
                    webForm.setData({
                      ...webForm.data,
                      is_used_by_classification_group: e.currentTarget.checked,
                    })
                  }
                  size={'md'}
                  colorScheme="brand"
                >
                  Make this note available to entire Class Group.
                </Checkbox>
              </FormControl>

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
                  Make this note available to entire Institution Group.
                </Checkbox>
              </FormControl>

              <FormControl>
                <FormButton
                  isLoading={webForm.processing}
                  isDisabled={webForm_genWithAi.processing}
                />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
