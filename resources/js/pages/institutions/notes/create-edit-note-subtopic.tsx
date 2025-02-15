import React from 'react';
import { FormControl, VStack, Checkbox, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { NoteSubTopic, NoteTopic } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Editor } from '@tinymce/tinymce-react';
import { Input } from '@chakra-ui/react';
import { NoteStatusType } from '@/types/types';

interface Props {
  noteTopic?: NoteTopic; //Supplied during CREATE
  noteSubTopic?: NoteSubTopic; //Supplied during EDIT
}

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

export default function CreateOrUpdateEvent({
  noteTopic,
  noteSubTopic,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  noteTopic = noteSubTopic ? noteSubTopic.note_topic : noteSubTopic;

  const webForm = useWebForm({
    noteTopic_id: noteTopic?.id,
    title: noteSubTopic ? noteSubTopic.title : '',
    content: noteSubTopic ? noteSubTopic.content : '',
    is_published: noteSubTopic
      ? noteSubTopic.status === NoteStatusType.Published
        ? true
        : false
      : true,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return noteSubTopic
        ? web.put(instRoute('note-sub-topics.update', [noteSubTopic?.id]), data)
        : web.post(instRoute('note-sub-topics.store', [noteTopic?.id]), data);
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('note-sub-topics.list', [noteTopic?.id]));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`${noteSubTopic ? 'Update' : 'Create'} Note`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <Text fontWeight={'semibold'}>
                {`${noteTopic?.course?.title} (${noteTopic?.classification?.title})`}
              </Text>
              <Text fontWeight={'semibold'}>
                {`PARENT TOPIC :: ${noteTopic?.title}`}
              </Text>

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
                title="Content"
                form={webForm as any}
                formKey="content"
                isRequired
              >
                <Editor
                  // onInit={(evt, editor) => (editorRef.current = editor)}
                  apiKey={tinymceApiKey}
                  initialValue={`${
                    noteSubTopic
                      ? noteSubTopic.content
                      : '<p>..Type the Content..</p>'
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
