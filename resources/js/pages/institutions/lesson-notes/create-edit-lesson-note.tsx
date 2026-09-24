import React, { useState } from 'react';
import {
  FormControl,
  FormErrorMessage,
  FormLabel,
  VStack,
  Checkbox,
  HStack,
  Text,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { LessonNote, LessonPlan } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { BrandButton, FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Input } from '@chakra-ui/react';
import { NoteStatusType } from '@/types/types';
import TinyMceEditor from '@/components/tinymce-editor';
import { Div } from '@/components/semantic';
import FileDropper from '@/components/file-dropper';
import FileObject from '@/components/file-dropper/file-object';
import { FileDropperType } from '@/components/file-dropper/common';
import MediaAttachmentsList from '@/components/media-attachments-list';

interface Props {
  lessonPlan?: LessonPlan;
  lessonNote?: LessonNote;
}

export default function CreateOrUpdateEvent({ lessonPlan, lessonNote }: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [lessonNoteFiles, setLessonNoteFiles] = useState<FileObject[]>([]);

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

  const webFormGenWithAi = useWebForm({
    topic_id: 0,
    title: '',
  });

  const topicId = lessonNote
    ? lessonNote.topic_id
    : lessonPlan?.scheme_of_work?.topic_id;
  const fileError = (webForm.errors as Record<string, string>).file;

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      if (lessonNote || lessonNoteFiles.length === 0) {
        return web.post(
          instRoute('lesson-notes.store-or-update', lessonNote ?? [lessonNote]),
          data
        );
      }

      const formData = new FormData();
      Object.entries(data).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
          return;
        }

        formData.append(
          key,
          typeof value === 'boolean' ? (value ? '1' : '0') : String(value)
        );
      });
      formData.append(
        'file',
        lessonNoteFiles[0].file,
        lessonNoteFiles[0].getNameWithExtension()
      );

      return web.post(
        instRoute('lesson-notes.store-or-update', lessonNote ?? [lessonNote]),
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
    });

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('inst-topics.show', [topicId]));
  };

  const genNoteWithAi = async () => {
    const response = await webFormGenWithAi.submit((data, web) => {
      data.topic_id = topicId ?? 0;
      data.title = webForm.data.title;
      return web.post(instRoute('lesson-notes.gen-ai-note'), data);
    });

    webForm.setValue('content', response.data?.result);

    if (!handleResponseToast(response)) {
      return;
    }
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Div fontSize={'lg'} fontWeight={'bold'} mb={2}>
          {lessonPlan?.scheme_of_work?.topic?.title}
        </Div>
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
                <TinyMceEditor
                  initialValue={lessonNote?.content}
                  value={webForm.data.content}
                  onEditorChange={(content: string) =>
                    webForm.setValue('content', content)
                  }
                />
              </FormControlBox>

              <FormControl>
                <HStack justifyContent={'end'}>
                  {/* <Checkbox
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
                  </Checkbox> */}
                  <BrandButton
                    size="xs"
                    variant={'outline'}
                    title="Generate with AI"
                    isLoading={webFormGenWithAi.processing}
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

              {!lessonNote && (
                <FormControl isInvalid={!!fileError}>
                  <FormLabel mb={0}>Supporting document</FormLabel>
                  <FileDropper
                    files={lessonNoteFiles}
                    onChange={(files) => setLessonNoteFiles(files.slice(0, 1))}
                    accept={[FileDropperType.Media]}
                    multiple={false}
                    canRename={false}
                    maxSize={10 * 1024 * 1024}
                    isLoading={webForm.processing}
                  />
                  <Text fontSize="sm" color="blackAlpha.700" mt={1}>
                    Optional. Images and supported documents up to 10MB can be
                    attached while creating the note.
                  </Text>
                  <FormErrorMessage>{fileError}</FormErrorMessage>
                </FormControl>
              )}

              {lessonNote && (
                <FormControl>
                  <FormLabel mb={0}>Current attachment</FormLabel>
                  <Text fontSize="sm" color="blackAlpha.700" mb={2}>
                    Attachments cannot be changed while editing a lesson note.
                  </Text>
                  <MediaAttachmentsList
                    media={lessonNote.media}
                    emptyText="No document attached."
                  />
                </FormControl>
              )}

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
                  isDisabled={webFormGenWithAi.processing}
                />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
