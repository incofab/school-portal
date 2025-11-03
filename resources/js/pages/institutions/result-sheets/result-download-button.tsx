import React from 'react';
import { Button, ButtonProps } from '@chakra-ui/react';
import { User } from '@/types/models';
import useWebForm from '@/hooks/use-web-form';
import route from '@/util/route';
import ResultUtil from '@/util/result-util';

interface Props {
  signed_url?: string;
  filename: string;
}

export default function ResultDownloadButton({
  signed_url,
  filename,
  ...props
}: Props & ButtonProps) {
  const downloadPdfForm = useWebForm({});

  async function downloadAsPdf() {
    if (!confirm('Do you want to download this result?')) {
      return;
    }
    // const filename = `${validFilename(student.user?.full_name)}-result-${
    //   termResult.term
    // }-${termResult.id}.pdf`;
    if (!signed_url) {
      alert('No signed url found');
      return;
    }
    window.location.href = route('pdf-bridge', {
      filename,
      url: signed_url,
    });
  }

  function exportPdf() {
    const nameWithoutExt = filename.replace(/\.[^/.]+$/, '');
    ResultUtil.exportAsPdf('result-sheet', nameWithoutExt); //user?.full_name + 'result-sheet');
  }

  return (
    <Button
      id={'download-btn'}
      {...props}
      onClick={() => {
        // downloadAsPdf();
        exportPdf();
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
