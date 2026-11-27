import React from 'react';
import { CertificateArtworkData } from './types';
import { Div } from '@/components/semantic';

export function InstitutionAddress({ data }: { data: CertificateArtworkData }) {
  return data.schoolAddress ? (
    <Div className="certificate-school-address">{data.schoolAddress}</Div>
  ) : null;
}

export function RecipientType({ data }: { data: CertificateArtworkData }) {
  return data.recipientType ? (
    <p className="certificate-recipient-type">{data.recipientType}</p>
  ) : null;
}

export function CertificateNotes({ data }: { data: CertificateArtworkData }) {
  if (!data.schoolMotto && !data.customFooter) return null;
  return (
    <div className="certificate-notes">
      {data.schoolMotto && <span>{data.schoolMotto}</span>}
      {data.customFooter && <span>{data.customFooter}</span>}
    </div>
  );
}
