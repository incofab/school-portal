import React from 'react';
import { Div } from '@/components/semantic';

interface Props {
  path: string;
}

export default function ShowPdfResult({ path }: Props) {
  return (
    <object data={path} type="application/pdf" width="100%" height="800px">
      <p>
        Alternative text - include a link{' '}
        <a href="http://africau.edu/images/default/sample.pdf">to the PDF!</a>
      </p>
    </object>
  );
}
