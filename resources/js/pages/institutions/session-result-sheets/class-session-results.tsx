import { Classification } from '@/types/models';
import React from 'react';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import SessionResultTemplate1, {
  SessionResultProps,
} from './session-result-template-1';

interface Props {
  classSessionResults: SessionResultProps[];
  classification: Classification;
}

export default function ClassSessionResults({
  classSessionResults,
  classification,
}: Props) {
  const { currentInstitution } = useSharedProps();

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
          resultCommentTemplate={sessionResultProps.resultCommentTemplate}
        />
      ))}
    </Div>
  );
}
