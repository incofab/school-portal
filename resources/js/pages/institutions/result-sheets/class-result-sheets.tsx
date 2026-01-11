import React from 'react';
import { Div } from '@/components/semantic';
import { ResultProps } from '@/util/result-util';
import { AcademicSession, Classification } from '@/types/models';
import MultipleResultSheets from './multiple-result-sheets';
import { Divider } from '@chakra-ui/react';
import { ucFirst } from '@/util/util';
import useDownloadHtml from '@/util/download-html';

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
  const { DownloadButton } = useDownloadHtml();

  return (
    <Div px={3}>
      <Div className="hidden-on-print">
        <Div textAlign={'center'}>
          <Div fontWeight={'bold'} fontSize={'2xl'}>
            {classification.title}
          </Div>
          <Div fontWeight={'bold'} fontSize={'md'}>
            {academicSession.title} - {ucFirst(term)} {forMidTerm ? 'Mid ' : ''}
            Result Sheets
          </Div>
        </Div>
        <DownloadButton
          filename={`${classification.title} ${academicSession.title} ${term} ${
            forMidTerm ? 'Mid ' : ''
          } Result Sheets`}
          title="Download All"
          mb={3}
        />
        <Divider my={2} />
      </Div>
      <Div>
        <MultipleResultSheets
          results={results}
          resultTemplete={resultTemplete}
        />
      </Div>
    </Div>
  );
}
