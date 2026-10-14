import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template6({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-06">
      <svg
        className="certificate-botanical-art certificate-botanical-left"
        viewBox="0 0 240 650"
        aria-hidden="true"
      >
        <path d="M25 630C176 490 55 359 192 206 220 175 195 92 147 30" />
        <path d="M46 590c-19-46-15-82 24-112 17 44 8 79-24 112Zm47-92c-4-48 12-81 60-98 2 47-18 78-60 98Zm33-129c-1-50 22-80 70-89-7 47-31 76-70 89Zm42-124c-18-45-10-81 30-112 18 44 9 80-30 112Zm-31-87c-44-17-65-48-61-96 43 19 64 51 61 96Z" />
      </svg>
      <svg
        className="certificate-botanical-art certificate-botanical-right"
        viewBox="0 0 240 650"
        aria-hidden="true"
      >
        <path d="M25 630C176 490 55 359 192 206 220 175 195 92 147 30" />
        <path d="M46 590c-19-46-15-82 24-112 17 44 8 79-24 112Zm47-92c-4-48 12-81 60-98 2 47-18 78-60 98Zm33-129c-1-50 22-80 70-89-7 47-31 76-70 89Zm42-124c-18-45-10-81 30-112 18 44 9 80-30 112Zm-31-87c-44-17-65-48-61-96 43 19 64 51 61 96Z" />
      </svg>
      <div className="certificate-botanical-frame" />
      <header className="certificate-school">
        {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
        <span>{data.institutionName}</span>
        <InstitutionAddress data={data} />
      </header>
      <div className="certificate-copy certificate-copy-centered">
        <p className="certificate-kicker">Grown through patience and purpose</p>
        <p className="certificate-presentation">With gratitude, we celebrate</p>
        <h2>{data.recipientName}</h2>
        <RecipientType data={data} />
        <h1>{data.awardType}</h1>
        <p className="certificate-citation">
          {data.citation ||
            'A remarkable journey of care, courage and steady growth.'}
        </p>
        <div className="certificate-context">
          {data.classification}
          {data.academicSession && `  ·  ${data.academicSession}`}
        </div>
      </div>
      <footer className="certificate-signatures">
        <div>
          <strong>{data.signatoryName || data.principalName || ' '}</strong>
          <span>
            {data.signatoryTitle || data.principalTitle || 'School principal'}
          </span>
        </div>
        <div className="certificate-botanical-date">
          <b>❧</b>
          <strong>{data.issuedOn}</strong>
          <span>Date celebrated</span>
        </div>
        <div>
          <strong>{data.certificateNumber}</strong>
          <span>Certificate reference</span>
        </div>
      </footer>
      <CertificateNotes data={data} />
    </article>
  );
}
