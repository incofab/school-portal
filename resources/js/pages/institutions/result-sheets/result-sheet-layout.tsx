import React, { PropsWithChildren } from 'react';
import { ResultProps } from '@/util/result-util';
import { validFilename } from '@/util/util';
import PagePrintLayout from '@/domain/institutions/page-print-layout';
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
    <PagePrintLayout useBgStyle={useBgStyle} filename={filename}>
      {children}
    </PagePrintLayout>
  );
}
