import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template1({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-01">
      <svg
        className="certificate-engraving"
        viewBox="0 0 1200 848"
        aria-hidden="true"
      >
        <path d="M30 205V30h225M1170 205V30H945M30 643v175h225m915-175v175H945" />
        <path d="M48 174c55-6 73-24 79-79 6 55 24 73 79 79-55 6-73 24-79 79-6-55-24-73-79-79Zm867 0c55-6 73-24 79-79 6 55 24 73 79 79-55 6-73 24-79 79-6-55-24-73-79-79ZM48 674c55-6 73-24 79-79 6 55 24 73 79 79-55 6-73 24-79 79-6-55-24-73-79-79Zm867 0c55-6 73-24 79-79 6 55 24 73 79 79-55 6-73 24-79 79-6-55-24-73-79-79Z" />
        <circle cx="600" cy="102" r="35" />
      </svg>
      <div className="certificate-classic-topline">
        HONOUR • INTEGRITY • EXCELLENCE
      </div>
      <header className="certificate-school">
        {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
        <span>{data.institutionName}</span>
        <InstitutionAddress data={data} />
      </header>
      <div className="certificate-classic-emblem" aria-hidden="true">
        <svg viewBox="0 0 64 64">
          <path d="M32 4 39 23 59 24 43 37 49 57 32 45 15 57 21 37 5 24 25 23Z" />
          <circle cx="32" cy="32" r="27" />
        </svg>
      </div>
      <div className="certificate-copy certificate-copy-centered">
        <p className="certificate-kicker">Certificate of recognition</p>
        <h1>{data.awardType}</h1>
        <p className="certificate-presentation">is proudly conferred upon</p>
        <h2>{data.recipientName}</h2>
        <RecipientType data={data} />
        <p className="certificate-citation">
          {data.citation ||
            'In recognition of commitment, character and outstanding achievement.'}
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
            {data.signatoryTitle ||
              data.principalTitle ||
              'Authorised signatory'}
          </span>
        </div>
        <div className="certificate-seal-date">
          <strong>{data.issuedOn}</strong>
          <span>Date conferred</span>
        </div>
      </footer>
      <div className="certificate-footnote">
        <span>RECORD {data.certificateNumber}</span>
        <span>{data.schoolMotto}</span>
      </div>
      <CertificateNotes data={{ ...data, schoolMotto: null }} />
    </article>
  );
}
