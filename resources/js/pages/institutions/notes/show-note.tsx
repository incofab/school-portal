import React from 'react';
import { Note } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DOMPurify from 'dompurify';

interface Props {
  note: Note;
}

export default function ShowNoteTopic({ note }: Props) {
  const sanitizedContent = DOMPurify.sanitize(note.content);

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={`TITLE :: ${note.title}`} />

        <SlabBody>
          <div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: sanitizedContent }}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
