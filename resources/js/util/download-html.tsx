import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { anchorDownload, sanitizeFilename } from './util';
import { BrandButton } from '@/components/buttons';
import { ButtonProps } from '@chakra-ui/react';

export default function useDownloadHtml() {
  const pdfUrl = import.meta.env.VITE_PDF_URL;
  const webForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function downloadPage(name: string) {
    const html = document.documentElement.outerHTML;
    return downloadHtml(html, name);
  }

  async function downloadHtml(html: string, name: string) {
    const res = await webForm.submit((data, web) => {
      const formData = new FormData();
      name = sanitizeFilename(name);
      const blob = new Blob([html], { type: 'text/html' });
      const file = new File([blob], `${name}.html`, { type: 'text/html' });

      formData.append('file', file);
      formData.append('name', name);
      return web.post(`${pdfUrl}/file-to-pdf`, formData);
    });
    console.log('res', res.data);

    if (!handleResponseToast(res)) return;

    anchorDownload(res.data.url, name);
  }

  function DownloadButton({
    filename,
    title,
    ...props
  }: {
    filename: string;
    title?: string;
  } & ButtonProps) {
    return (
      <BrandButton
        onClick={() => downloadPage(filename)}
        title={title || 'Download'}
        isLoading={webForm.processing}
        className="hidden-on-print"
        {...props}
      />
    );
  }

  return {
    downloadHtml,
    downloadPage,
    downloadForm: webForm,
    DownloadButton,
  };
}
