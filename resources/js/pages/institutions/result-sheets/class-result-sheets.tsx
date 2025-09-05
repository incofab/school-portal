import React from 'react';
import { Div } from '@/components/semantic';
import { ResultProps } from '@/util/result-util';
import { AcademicSession, Classification } from '@/types/models';
import MultipleResultSheets from './multiple-result-sheets';
import { Divider } from '@chakra-ui/react';
import { ucFirst } from '@/util/util';

interface Props {
  classification: Classification;
  academicSession: AcademicSession;
  term: string;
  forMidTerm: boolean;
  results: ResultProps[];
  resultTemplete: string;
}
export default function ClassResultSheets({
  classification,
  academicSession,
  term,
  forMidTerm,
  results,
  resultTemplete,
}: Props) {
  console.log('resultTemplete', resultTemplete);

  return (
    <Div>
      <Div px={3} textAlign={'center'}>
        <Div fontWeight={'bold'} fontSize={'2xl'}>
          {classification.title}
        </Div>
        <Div fontWeight={'bold'} fontSize={'md'}>
          {academicSession.title} - {ucFirst(term)} {forMidTerm ? 'Mid ' : ''}
          Result Sheets
        </Div>
      </Div>
      <Divider my={2} />
      <Div>
        <MultipleResultSheets
          results={results}
          resultTemplete={resultTemplete}
        />
      </Div>
    </Div>
  );
}
