import React from 'react';
import { Event, TokenUser } from '@/types/models';
import CreateExamComponent from '../component/create-exam-component';
import ExamLayout from '../exam-layout';
import useInstitutionRoute from '@/hooks/use-institution-route';

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
  const { instRoute } = useInstitutionRoute();
  return (
    <ExamLayout
      examable={tokenUser}
      title={event.title}
      rightElement={tokenUser.name}
      breadCrumbItems={[
        { title: 'Event', href: instRoute('external.events.show', [event]) },
        { title: 'Create Exam', href: '#' },
      ]}
    >
      <CreateExamComponent
        event={event}
        examable={tokenUser}
        examable_type={examable_type}
      />
    </ExamLayout>
  );
}
