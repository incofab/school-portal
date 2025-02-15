import React from 'react';
import { SchemeOfWork } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DOMPurify from 'dompurify';
import { Div } from '@/components/semantic';
import { Center, Heading } from '@chakra-ui/react';

interface Props {
  schemeOfWork: SchemeOfWork;
}

export default function ShowSchemeOfWork({ schemeOfWork }: Props) {
  const learningObjectives = DOMPurify.sanitize(
    schemeOfWork.learning_objectives
  );
  const resources = DOMPurify.sanitize(schemeOfWork.resources);

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`Scheme of Work  (Week ${schemeOfWork.week_number})`}
        />

        <SlabBody>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            WEEK :: {schemeOfWork.week_number}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            TERM :: {schemeOfWork.term}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="50px">
            TOPIC :: {schemeOfWork.topic?.title}
          </Heading>
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            LEARNING OBJECTIVES ::
          </Heading>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: learningObjectives }}
          />
          <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
            RESOURCES ::
          </Heading>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: resources }}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
