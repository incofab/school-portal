import React from 'react';
import { HStack, Icon, Spacer, Text } from '@chakra-ui/react';
import { Div } from './semantic';
import {
  LearningEvaluation,
  LearningEvaluationDomain,
  TermResult,
} from '@/types/models';
import { LearningEvaluationDomainType } from '@/types/types';
import { CheckIcon } from '@heroicons/react/24/solid';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  termResult: TermResult;
  learningEvaluations: LearningEvaluation[];
}

export default function DisplayTermResultEvaluation({
  termResult,
  learningEvaluations,
}: Props) {
  type ColumnType = { [key: string]: LearningEvaluation };
  const rowIndexTrack = {} as { [key: string]: number };
  const rows = [] as {
    index: number;
    column: ColumnType;
  }[];

  const headers = {} as { [key: string]: string };

  learningEvaluations.map((item) => {
    const domain = item.learning_evaluation_domain!;
    const rowIndex = rowIndexTrack[domain.title] ?? 0;

    const currentRow = rows[rowIndex] ?? {};
    currentRow.column = { ...(currentRow.column ?? {}), [domain.title]: item };

    rowIndexTrack[domain.title] = rowIndex + 1;
    rows[rowIndex] = currentRow;
    headers[domain.title] = domain.title;
  });

  return (
    <Div>
      <table style={{ width: '100%', textAlign: 'left' }}>
        <thead>
          <tr style={{ fontWeight: 'bold' }}>
            {Object.entries(headers).map(([key, item]) => (
              <th style={{ border: '1px solid #000' }} key={'header' + key}>
                {key}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((rowItem, index) => {
            return (
              <tr key={'row' + index}>
                {Object.entries(headers).map(([columnHeader, item], index) => {
                  const evaluation = rowItem.column[columnHeader];
                  const rowValue = termResult.learning_evaluation
                    ? termResult.learning_evaluation[evaluation?.id]
                    : null;
                  return (
                    <td
                      style={{
                        border: '1px solid #000',
                        paddingLeft: '5px',
                        paddingRight: '5px',
                        paddingTop: '2px',
                        paddingBottom: '2px',
                      }}
                      key={'td' + index + columnHeader}
                    >
                      <HStack>
                        <Text>{evaluation?.title}</Text>
                        <Spacer />
                        <DisplayEvaluationValue
                          value={rowValue}
                          learningEvaluationDomain={
                            evaluation?.learning_evaluation_domain
                          }
                          learningEvaluation={evaluation}
                        />
                      </HStack>
                    </td>
                  );
                })}
              </tr>
            );
          })}
        </tbody>
      </table>
    </Div>
  );
}

function DisplayEvaluationValue({
  value,
  learningEvaluationDomain,
  learningEvaluation,
}: {
  value: any;
  learningEvaluationDomain?: LearningEvaluationDomain;
  learningEvaluation?: LearningEvaluation;
}) {
  const { currentInstitution } = useSharedProps();
  // if (!value || !learningEvaluationDomain) {
  //   return null;
  // }
  if (
    currentInstitution.name.toLowerCase().includes('wisegate') ||
    currentInstitution.name.toLowerCase().includes('wise gate')
  ) {
    return wiseGate(learningEvaluation);
  }
  let element = <></>;
  switch (learningEvaluationDomain?.type) {
    case LearningEvaluationDomainType.Number:
    case LearningEvaluationDomainType.Text:
      element = <Text>{value}</Text>;
      break;
    case LearningEvaluationDomainType.YesOrNo:
      element = value ? <Icon as={CheckIcon} fontWeight={'bold'} /> : <></>;
      break;
  }
  return (
    <Div
      fontWeight={'bold'}
      px={1}
      border={'2px solid #000'}
      width={'35px'}
      height={'25px'}
      textAlign={'center'}
    >
      {element}
    </Div>
  );
}

function wiseGate(learningEvaluation?: LearningEvaluation) {
  const forA: string[] = ['Cooperation', 'Reliability'];
  const forC: string[] = [];
  let element = '';
  // console.log('title', learningEvaluation?.title, learningEvaluation);

  if (forA.includes(learningEvaluation?.title ?? '')) {
    element = 'A';
  } else if (forC.includes(learningEvaluation?.title ?? '')) {
    element = 'C';
  } else {
    element = 'B';
  }

  return <Text>{element}</Text>;
}
