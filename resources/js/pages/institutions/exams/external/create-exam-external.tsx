import React from 'react';
import { Event, TokenUser } from '@/types/models';
import { Div } from '@/components/semantic';
import CreateExamComponent from '../component/create-exam-component';
import ExamLayout from '../exam-layout';

interface Props {
  event: Event;
  tokenUser: TokenUser;
}

export default function CreateExamExternal({ event, tokenUser }: Props) {
  return (
    <ExamLayout title={tokenUser.name}>
      <CreateExamComponent event={event} tokenUser={tokenUser} />
    </ExamLayout>
  );
}
