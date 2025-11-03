import React, { PropsWithChildren } from 'react';
// import { useColorMode } from '@chakra-ui/react';
// import { Div } from '@/components/semantic';
// import useSharedProps from '@/hooks/use-shared-props';
import { validFilename } from '@/util/util';
import { User } from '@/types/models';
// import ResultDownloadButton from '../../result-sheets/result-download-button';
import PagePrintLayout from '@/domain/institutions/page-print-layout';

interface Props {
  useBgStyle?: boolean;
  user: User;
}

export default function ReceiptLayout({
  children,
  useBgStyle,
  user,
}: Props & PropsWithChildren) {
  // const { currentInstitution } = useSharedProps();
  // const { colorMode, setColorMode } = useColorMode();
  // if (colorMode !== 'light') {
  //   setColorMode('light');
  // }

  // const svgCode = `<svg xmlns='http://www.w3.org/2000/svg' width='140' height='100' opacity='0.08' viewBox='0 0 100 100' transform='rotate(45)'><text x='0' y='50' font-size='18' fill='%23000'>${currentInstitution.name}</text></svg>`;
  // const backgroundStyle = {
  //   backgroundImage: `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(
  //     svgCode
  //   )}")`,
  //   backgroundRepeat: 'repeat',
  //   backgroundColor: 'white',
  // };
  const filename = `${validFilename(user?.full_name)}-receipt.pdf`;
  return (
    <PagePrintLayout useBgStyle={useBgStyle} filename={filename}>
      {children}
    </PagePrintLayout>
    // <Div>
    //   <Div
    //     style={useBgStyle ? backgroundStyle : undefined}
    //     minHeight={'1170px'}
    //   >
    //     <ResultDownloadButton
    //       // signed_url={''}
    //       // termResult={resultProps.termResult}
    //       filename={filename}
    //     />
    //     {children}
    //   </Div>
    // </Div>
  );
}
