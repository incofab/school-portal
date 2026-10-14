import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template3({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-03">
      <svg
        className="certificate-laurel-art"
        viewBox="0 0 1000 500"
        aria-hidden="true"
      >
        <path d="M500 445C290 410 160 265 170 80m330 365c210-35 340-180 330-365" />
        <path d="M184 136c52-5 84-29 101-68-52 6-85 28-101 68Zm29 78c50-10 78-38 91-79-51 11-81 38-91 79Zm52 72c47-18 70-50 75-93-48 19-73 51-75 93Zm76 62c41-26 58-61 54-103-43 27-59 62-54 103Zm475-284c-52-5-84-29-101-68 52 6 85 28 101 68Zm-29 78c-50-10-78-38-91-79 51 11 81 38 91 79Zm-52 72c-47-18-70-50-75-93 48 19 73 51 75 93Zm-76 62c-41-26-58-61-54-103 43 27 59 62 54 103Z" />
        <circle cx="500" cy="92" r="48" />
        <path d="m500 57 10 24 26 2-20 17 7 25-23-14-23 14 7-25-20-17 26-2Z" />
      </svg>
      <div className="certificate-academic-header">
        <header className="certificate-school">
          {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
          <span>{data.institutionName}</span>
          <InstitutionAddress data={data} />
        </header>
        <span className="certificate-academic-motto">
          A RECORD OF LEARNING AND CHARACTER
        </span>
      </div>
      <div className="certificate-copy certificate-copy-centered">
        <p className="certificate-kicker">The academic council recognises</p>
        <h2>{data.recipientName}</h2>
        <RecipientType data={data} />
        <p className="certificate-academic-award">{data.awardType}</p>
        <p className="certificate-citation">
          {data.citation ||
            'For intellectual curiosity, perseverance and distinguished progress.'}
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
            {data.signatoryTitle || data.principalTitle || 'Academic director'}
          </span>
        </div>
        <div className="certificate-academic-seal">
          ACADEMIC
          <br />
          RECORD
          <br />
          <b>{data.issuedOn}</b>
        </div>
        <div>
          <strong>{data.certificateNumber}</strong>
          <span>Registry reference</span>
        </div>
      </footer>
      <CertificateNotes data={data} />
    </article>
  );
}
