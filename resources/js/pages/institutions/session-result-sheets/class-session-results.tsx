import { Classification, ResultCommentTemplate } from '@/types/models';
import React from 'react';
import { Div } from '@/components/semantic';
import SessionResultTemplate1, {
  SessionResultProps,
} from './session-result-template-1';

type ClassSessionResult = Omit<SessionResultProps, 'resultCommentTemplate'>;

interface Props {
  classSessionResults: ClassSessionResult[];
  classification: Classification;
  resultCommentTemplate: ResultCommentTemplate[];
}

export default function ClassSessionResults({
  classSessionResults,
  classification,
  resultCommentTemplate,
}: Props) {
  return (
    <Div>
      <Div>
        <Div fontWeight={'bold'} fontSize={'2xl'} mb={1}>
          {classification.title} Session Results
        </Div>
      </Div>
      {classSessionResults.map((sessionResultProps) => (
        <SessionResultTemplate1
          key={sessionResultProps.sessionResult.id}
          sessionResult={sessionResultProps.sessionResult}
          termResultDetails={sessionResultProps.termResultDetails}
          resultCommentTemplate={resultCommentTemplate}
        />
      ))}
    </Div>
  );
}
