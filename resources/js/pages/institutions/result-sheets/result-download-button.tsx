import React from 'react';
import { Button, ButtonProps } from '@chakra-ui/react';
import route from '@/util/route';
import ResultUtil from '@/util/result-util';

interface Props {
  signed_url?: string;
  filename: string;
}

const pdfUrl = import.meta.env.VITE_PDF_URL;

export default function ResultDownloadButton({
  signed_url,
  filename,
  ...props
}: Props & ButtonProps) {
  async function downloadAsPdf() {
    if (!confirm('Do you want to download this result?')) {
      return;
    }
    // window.location.href = route('pdf-bridge', {
    //   filename,
    //   url: signed_url,
    // });
    window.location.href = `${pdfUrl}?url=${encodeURIComponent(
      signed_url!
    )}&name=${encodeURIComponent(filename)}`;
  }

  function exportPdf() {
    const nameWithoutExt = filename.replace(/\.[^/.]+$/, '');
    ResultUtil.exportAsPdf('result-sheet', nameWithoutExt);
  }

  return (
    <Button
      id={'download-btn'}
      {...props}
      onClick={() => {
        if (signed_url) {
          downloadAsPdf();
        } else {
          exportPdf();
        }
      }}
      size={'sm'}
      variant={'outline'}
      colorScheme="brand"
    >
      Download
    </Button>
  );
}
