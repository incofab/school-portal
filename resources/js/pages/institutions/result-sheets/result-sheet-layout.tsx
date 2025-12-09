import React, { PropsWithChildren } from 'react';
import { ResultProps } from '@/util/result-util';
import { formatAsDate, validFilename } from '@/util/util';
import PagePrintLayout from '@/domain/institutions/page-print-layout';
import { LabelText } from '@/components/result-helper-components';
interface Props {
  useBgStyle?: boolean;
  resultProps: ResultProps;
}

export default function ResultSheetLayout({
  children,
  resultProps,
  useBgStyle,
}: Props & PropsWithChildren) {
  const filename = `${validFilename(
    resultProps.student.user?.full_name
  )}-result-${resultProps.termResult.term}-${resultProps.termResult.id}.pdf`;
  return (
    <PagePrintLayout
      useBgStyle={useBgStyle}
      filename={filename}
      signed_url={resultProps.signed_url}
      contentId={'result-sheet'}
    >
      {children}
    </PagePrintLayout>
  );
}

export function ClosingDate({ resultProps }: { resultProps: ResultProps }) {
  return resultProps.termDetail?.end_date ? (
    <LabelText
      label="Closing Date"
      text={formatAsDate(resultProps.termDetail.end_date)}
    />
  ) : null;
}

export function OpeningDate({ resultProps }: { resultProps: ResultProps }) {
  return resultProps.termDetail?.start_date ? (
    <LabelText
      label="Opening Date"
      text={formatAsDate(resultProps.termDetail.start_date)}
    />
  ) : null;
}

export function NextTermDate({ resultProps }: { resultProps: ResultProps }) {
  const nextTermResumptionDate =
    resultProps.classResultInfo.next_term_resumption_date ??
    resultProps.termDetail?.next_term_resumption_date;
  return nextTermResumptionDate ? (
    <LabelText
      label="Next Term Begins"
      text={formatAsDate(nextTermResumptionDate)}
    />
  ) : null;
}
