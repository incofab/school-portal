import React from 'react';
import { Button, ButtonProps } from '@chakra-ui/react';
import ResultUtil from '@/util/result-util';

interface Props {
  signed_url?: string;
  filename: string;
  contentId: string;
}

const pdfUrl = import.meta.env.VITE_PDF_URL;

export default function PageDownloadButton({
  signed_url,
  filename,
  contentId,
  ...props
}: Props & ButtonProps) {
  async function downloadAsPdf() {
    if (!confirm('Do you want to download this result?')) {
      return;
    }
    window.location.href = `${pdfUrl}?url=${encodeURIComponent(
      signed_url!
    )}&name=${encodeURIComponent(filename)}`;
  }

  function exportPdf() {
    const nameWithoutExt = filename.replace(/\.[^/.]+$/, '');
    ResultUtil.exportAsPdf(contentId, nameWithoutExt);
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
