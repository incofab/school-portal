import React, { useState } from 'react';
import { LessonNote, Media } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import DOMPurify from 'dompurify';
import { Div } from '@/components/semantic';
import {
  Divider,
  Heading,
  Text,
  useColorModeValue,
  VStack,
} from '@chakra-ui/react';
import { LabelText } from '@/components/result-helper-components';
import { ucFirst } from '@/util/util';
import MediaAttachmentsList from '@/components/media-attachments-list';
import FileDropper from '@/components/file-dropper';
import FileObject from '@/components/file-dropper/file-object';
import { FileDropperType } from '@/components/file-dropper/common';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  lessonNote: LessonNote;
}

export default function ShowLessonNote({ lessonNote }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const uploadWebForm = useWebForm({});
  const deleteWebForm = useWebForm({});
  const [uploadFiles, setUploadFiles] = useState<FileObject[]>([]);
  const [deletingMediaId, setDeletingMediaId] = useState<number | null>(null);
  const sanitizedContent = DOMPurify.sanitize(lessonNote.content);

  const uploadMedia = async (files: FileObject[]) => {
    if (files.length === 0) {
      setUploadFiles([]);
      return;
    }

    const formData = new FormData();
    formData.append('file', files[0].file, files[0].getNameWithExtension());

    const res = await uploadWebForm.submit((data: any, web: any) =>
      web.post(instRoute('lesson-notes.media.store', [lessonNote]), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    );

    setUploadFiles([]);
    if (!handleResponseToast(res)) return;

    Inertia.reload({ only: ['lessonNote'], preserveScroll: true });
  };

  const deleteMedia = async (media: Media) => {
    setDeletingMediaId(media.id);
    const res = await deleteWebForm.submit((data: any, web: any) =>
      web.delete(instRoute('lesson-notes.media.destroy', [lessonNote, media]))
    );

    setDeletingMediaId(null);
    if (!handleResponseToast(res)) return;

    Inertia.reload({ only: ['lessonNote'], preserveScroll: true });
  };

  return (
    <DashboardLayout
      mainBarProps={{ background: useColorModeValue('white', 'gray.900') }}
    >
      <Div p={5}>
        <Heading size={'md'} fontWeight={'bold'}>
          LESSON NOTE
        </Heading>
        <Divider mt={2} />

        <Div>
          {/* <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            CLASS :: {lessonNote.classification?.title}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            SUBJECT :: {lessonNote.course?.title}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="50px">
            TITLE :: {lessonNote.title}
          </Heading>

          */}
          {[
            { label: 'CLASS', value: lessonNote.classification?.title },
            { label: 'SUBJECT', value: lessonNote.course?.title },
            { label: 'TITLE', value: lessonNote.title },
            {
              label: 'TERM',
              value: ucFirst(
                `${lessonNote.lesson_plan?.scheme_of_work?.term} term`
              ),
            },
          ].map((item, index) => (
            <LabelText
              key={index}
              label={item.label}
              text={item.value}
              labelProps={{ fontWeight: 'bold' }}
              my={2}
            />
          ))}

          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px" mt={4}>
            CONTENT ::
          </Heading>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: sanitizedContent }}
          />

          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            ATTACHMENTS ::
          </Heading>
          <VStack align={'stretch'} spacing={3}>
            <FileDropper
              files={uploadFiles}
              onChange={uploadMedia}
              accept={[FileDropperType.Media]}
              multiple={false}
              canRename={false}
              isLoading={uploadWebForm.processing}
            />
            <Text fontSize={'sm'} color={'blackAlpha.700'}>
              Uploads are saved one file at a time.
            </Text>
            <MediaAttachmentsList
              media={lessonNote.media}
              onDelete={deleteMedia}
              deletingMediaId={deletingMediaId}
            />
          </VStack>
        </Div>
      </Div>
    </DashboardLayout>
  );
}
