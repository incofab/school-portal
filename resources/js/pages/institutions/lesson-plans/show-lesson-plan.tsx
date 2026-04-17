import React from 'react';
import { LessonPlan } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DOMPurify from 'dompurify';
import { Div } from '@/components/semantic';
import { Heading } from '@chakra-ui/react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { LinkButton } from '@/components/buttons';
import { LabelText } from '@/components/result-helper-components';
import { formatAsDate } from '@/util/util';

interface Props {
  lessonPlan: LessonPlan;
}

export default function ShowLessonPlan({ lessonPlan }: Props) {
  const { instRoute } = useInstitutionRoute();

  const objective = DOMPurify.sanitize(lessonPlan.objective);
  const activities = DOMPurify.sanitize(lessonPlan.activities);
  const content = DOMPurify.sanitize(lessonPlan.content);
  const schemeOfWork = lessonPlan.scheme_of_work;
  const topic = schemeOfWork?.topic;
  const isSubTopic = Boolean(topic?.parent_topic);

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
          {/* <LabelText label="Subject" text={topic?.course?.code} />
          <LabelText label="Theme" text={topic?.title} />
          <LabelText
            label="Topic"
            text={isSubTopic ? topic?.parent_topic?.title : topic?.title}
          />
          <LabelText label="Sub Topic" text={isSubTopic ? topic?.title : ''} />
          <LabelText label="Date" text={formatAsDate(lessonPlan.created_at)} />
          <LabelText label="Class" text={topic?.classification?.title} />
          <LabelText
            label="Number of Pupils"
            text={topic?.classification?.students_count}
          /> */}
          {[
            { label: 'Subject', text: topic?.course?.code },
            { label: 'Theme', text: topic?.title },
            {
              label: 'Topic',
              text: isSubTopic ? topic?.parent_topic?.title : topic?.title,
            },
            { label: 'Sub Topic', text: isSubTopic ? topic?.title : '' },
            { label: 'Date', text: formatAsDate(lessonPlan.created_at) },
            { label: 'Class', text: topic?.classification?.title },
            {
              label: 'No in Class',
              text: topic?.classification?.students_count,
            },
          ].map((item) => (
            <LabelText
              key={item.label}
              label={item.label}
              text={item.text}
              my={2}
              labelProps={{ fontWeight: 'bold', width: '120px' }}
            />
          ))}
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px" mt={5}>
            LEARNING OBJECTIVE ::
          </Heading>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: objective }}
          />

          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px" mt={3}>
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
