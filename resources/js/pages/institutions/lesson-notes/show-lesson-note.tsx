import React from 'react';
import { LessonNote } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DOMPurify from 'dompurify';
import { Div } from '@/components/semantic';
import { Divider, Heading, useColorModeValue } from '@chakra-ui/react';
import { LabelText } from '@/components/result-helper-components';
import { ucFirst } from '@/util/util';

interface Props {
  lessonNote: LessonNote;
}

export default function ShowLessonNote({ lessonNote }: Props) {
  const sanitizedContent = DOMPurify.sanitize(lessonNote.content);

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
        </Div>
      </Div>
    </DashboardLayout>
  );
}
