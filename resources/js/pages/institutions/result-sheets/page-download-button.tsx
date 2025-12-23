import React from 'react';
import { Button, ButtonProps } from '@chakra-ui/react';
import ResultUtil from '@/util/result-util';
import { anchorDownload, sanitizeFilename } from '@/util/util';
import useDownloadHtml from '@/util/download-html';

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
  const { downloadPage, downloadForm } = useDownloadHtml();
  filename = sanitizeFilename(filename);
  async function downloadAsPdf() {
    if (!confirm('Do you want to download this result?')) {
      return;
    }
    const url = `${pdfUrl}?url=${encodeURIComponent(
      signed_url!
    )}&name=${encodeURIComponent(filename)}`;

    anchorDownload(url, filename);
    // console.log('url', url);

    // const a = document.createElement('a');
    // a.href = url;
    // // a.download = filename; // may be ignored cross-origin
    // document.body.appendChild(a);
    // a.click();
    // a.remove();
  }

  function exportPdf() {
    // const nameWithoutExt = filename.replace(/\.[^/.]+$/, '');
    // ResultUtil.exportAsPdf(contentId, nameWithoutExt);

    downloadPage(filename);
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
      isLoading={downloadForm.processing}
    >
      Download
    </Button>
  );
}
