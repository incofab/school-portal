import React from 'react';
import { LessonPlan, SchemeOfWork } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DOMPurify from 'dompurify';
import { Div } from '@/components/semantic';
import { Center, Heading } from '@chakra-ui/react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { LinkButton } from '@/components/buttons';

interface Props {
  lessonPlan: LessonPlan;
}

export default function ShowLessonPlan({ lessonPlan }: Props) {
  const { instRoute } = useInstitutionRoute();

  const objective = DOMPurify.sanitize(lessonPlan.objective);
  const activities = DOMPurify.sanitize(lessonPlan.activities);
  const content = DOMPurify.sanitize(lessonPlan.content);

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`Lesson Plan  (Week ${lessonPlan.scheme_of_work?.week_number})`}
          rightElement={
            <LinkButton
              href={instRoute('lesson-plans.edit', [lessonPlan.id])}
              title={'Edit'}
            />
          }
        />

        <SlabBody>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            WEEK :: {lessonPlan.scheme_of_work?.week_number}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            TERM :: {lessonPlan.scheme_of_work?.term}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            SUBJECT :: {lessonPlan.scheme_of_work?.topic?.course?.title}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="50px">
            TOPIC :: {lessonPlan.scheme_of_work?.topic?.title}
          </Heading>

          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            OBJECTIVE ::
          </Heading>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: objective }}
          />

          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            ACTIVITIES ::
          </Heading>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: activities }}
          />

          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            CONTENT ::
          </Heading>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: content }}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
