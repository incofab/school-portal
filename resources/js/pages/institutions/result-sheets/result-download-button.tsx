import React from 'react';
import { Button, ButtonProps } from '@chakra-ui/react';
import { Student, TermResult } from '@/types/models';
import { validFilename } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import route from '@/util/route';
import ResultUtil from '@/util/result-util';

interface Props {
  termResult: TermResult;
  student: Student;
  signed_url: string;
}

export default function ResultDownloadButton({
  termResult,
  student,
  signed_url,
  ...props
}: Props & ButtonProps) {
  const downloadPdfForm = useWebForm({});

  async function downloadAsPdf() {
    if (!confirm('Do you want to download this result?')) {
      return;
    }
    const filename = `${validFilename(student.user?.full_name)}-result-${
      termResult.term
    }-${termResult.id}.pdf`;
    window.location.href = route('pdf-bridge', {
      filename,
      url: signed_url,
    });
  }

  function exportPdf() {
    ResultUtil.exportAsPdf(
      'result-sheet',
      student.user?.full_name + 'result-sheet'
    );
  }

  return (
    <Button
      id={'download-btn'}
      {...props}
      onClick={() => {
        downloadAsPdf();
      }}
      isLoading={downloadPdfForm.processing}
      size={'sm'}
      variant={'outline'}
      colorScheme="brand"
    >
      Download
    </Button>
  );
}
