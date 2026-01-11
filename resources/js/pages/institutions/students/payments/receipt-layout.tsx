import React, { PropsWithChildren } from 'react';
import { User } from '@/types/models';
import PagePrintLayout from '@/domain/institutions/page-print-layout';
import { generateUniqueString } from '@/util/util';

interface Props {
  useBgStyle?: boolean;
  user: User;
  contentId: string;
}

export default function ReceiptLayout({
  children,
  useBgStyle,
  user,
  contentId,
}: Props & PropsWithChildren) {
  const filename = `${user?.full_name ?? generateUniqueString('receipt')}.pdf`;
  return (
    <PagePrintLayout
      useBgStyle={useBgStyle}
      filename={filename}
      contentId={contentId}
    >
      {children}
    </PagePrintLayout>
  );
}
