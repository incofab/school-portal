import React from 'react';
import '@/style/certificates.css';
import Template1 from './template-1';
import Template2 from './template-2';
import Template3 from './template-3';
import Template4 from './template-4';
import Template5 from './template-5';
import Template6 from './template-6';
import Template7 from './template-7';
import Template8 from './template-8';
import Template9 from './template-9';
import { CertificateArtworkData } from './types';

const designs: Record<
  string,
  React.ComponentType<{ data: CertificateArtworkData }>
> = {
  'template-1.tsx': Template1,
  'template-2.tsx': Template2,
  'template-3.tsx': Template3,
  'template-4.tsx': Template4,
  'template-5.tsx': Template5,
  'template-6.tsx': Template6,
  'template-7.tsx': Template7,
  'template-8.tsx': Template8,
  'template-9.tsx': Template9,
};

interface Props {
  templatePath: string;
  data: CertificateArtworkData;
}

export default function CertificateArtwork({ templatePath, data }: Props) {
  const fileName = templatePath.split('/').pop() ?? '';
  const Design = designs[fileName] ?? Template1;
  return (
    <div
      className={`certificate-artwork${data.revoked ? ' is-revoked' : ''}`}
      style={
        { '--certificate-primary': data.primaryColor } as React.CSSProperties
      }
    >
      <Design data={data} />
      {data.revoked && <div className="certificate-revoked-stamp">REVOKED</div>}
    </div>
  );
}
