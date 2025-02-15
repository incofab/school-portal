import React from 'react';
import { LessonNote, Note } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DOMPurify from 'dompurify';
import { Div } from '@/components/semantic';
import { Heading } from '@chakra-ui/react';

interface Props {
  lessonNote: LessonNote;
}

export default function ShowNoteTopic({ lessonNote }: Props) {
  const sanitizedContent = DOMPurify.sanitize(lessonNote.content);

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={`LESSON NOTE`} />

        <SlabBody>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            CLASS :: {lessonNote.classification?.title}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            SUBJECT :: {lessonNote.course?.title}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="50px">
            TITLE :: {lessonNote.title}
          </Heading>

          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            CONTENT ::
          </Heading>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: sanitizedContent }}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
