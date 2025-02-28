import React from 'react';
import { Topic } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DOMPurify from 'dompurify';
import { Div } from '@/components/semantic';

interface Props {
  topic: Topic;
}

export default function ShowSubTopic({ topic }: Props) {
  const sanitizedContent = DOMPurify.sanitize(topic.description);

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={`TITLE :: ${topic.title}`} />

        <SlabBody>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: sanitizedContent }}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
