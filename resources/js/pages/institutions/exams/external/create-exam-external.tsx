import React from 'react';
import { Event, TokenUser } from '@/types/models';
import CreateExamComponent from '../component/create-exam-component';
import ExamLayout from '../exam-layout';

interface Props {
  event: Event;
  tokenUser: TokenUser;
  examable_type: string;
}

export default function CreateExamExternal({
  event,
  tokenUser,
  examable_type,
}: Props) {
  return (
    <ExamLayout title={event.title} rightElement={tokenUser.name}>
      <CreateExamComponent
        event={event}
        examable={tokenUser}
        examable_type={examable_type}
      />
    </ExamLayout>
  );
}
