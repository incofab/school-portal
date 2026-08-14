import React, { PropsWithChildren } from 'react';
import { HStack, useColorMode } from '@chakra-ui/react';
import { Div } from '@/components/semantic';
import useSharedProps from '@/hooks/use-shared-props';
import PageDownloadButton from '@/pages/institutions/result-sheets/page-download-button';
import ExcelExportButton from '@/components/excel-export-button';

interface Props {
  useBgStyle?: boolean;
  filename: string;
  contentId: string;
  signed_url?: string;
  exportToExcel?: boolean;
  excelSheetName?: string;
  excelTableSelector?: string;
}

export default function PagePrintLayout({
  children,
  useBgStyle,
  filename,
  contentId,
  signed_url: signedUrl,
  exportToExcel,
  excelSheetName,
  excelTableSelector,
}: Props & PropsWithChildren) {
  const { currentInstitution } = useSharedProps();
  const { colorMode, setColorMode } = useColorMode();
  if (colorMode !== 'light') {
    setColorMode('light');
  }

  const svgCode = `<svg xmlns='http://www.w3.org/2000/svg' width='140' height='100' opacity='0.08' viewBox='0 0 100 100' transform='rotate(45)'><text x='0' y='50' font-size='18' fill='%23000'>${currentInstitution.name}</text></svg>`;
  const backgroundStyle = {
    backgroundImage: `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(
      svgCode
    )}")`,
    backgroundRepeat: 'repeat',
    backgroundColor: 'white',
  };
  return (
    <Div w={'full'} maxW={'100vw'} overflowX={'auto'}>
      <Div
        style={useBgStyle === false ? undefined : backgroundStyle}
        // minHeight={'1170px'}
        minHeight={'900px'}
        w={'full'}
        maxW={'100vw'}
        minW={0}
      >
        <HStack className="hidden-on-print" spacing={2} mb={2}>
          <PageDownloadButton
            signed_url={signedUrl}
            // termResult={resultProps.termResult}
            filename={filename}
            contentId={contentId}
          />
          {exportToExcel && (
            <ExcelExportButton
              filename={filename}
              sheetName={excelSheetName}
              contentId={contentId}
              tableSelector={excelTableSelector}
              colorScheme="brand"
            />
          )}
        </HStack>
        {children}
      </Div>
    </Div>
  );
}
