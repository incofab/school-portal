import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template8({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-08">
      <svg
        className="certificate-prestige-ornament"
        viewBox="0 0 1000 700"
        aria-hidden="true"
      >
        <path d="M500 25c32 61 50 84 112 106-62 22-80 45-112 106-32-61-50-84-112-106 62-22 80-45 112-106Zm0 432c25 47 39 65 87 82-48 17-62 35-87 82-25-47-39-65-87-82 48-17 62-35 87-82ZM68 350c23 44 37 59 81 75-44 16-58 31-81 75-23-44-37-59-81-75 44-16 58-31 81-75Zm864-160c19 36 30 49 66 62-36 13-47 26-66 62-19-36-30-49-66-62 36-13 47-26 66-62Zm0 320c19 36 30 49 66 62-36 13-47 26-66 62-19-36-30-49-66-62 36-13 47-26 66-62Z" />
        <circle cx="500" cy="350" r="295" />
        <circle cx="500" cy="350" r="273" />
        <path d="M120 350h760M500 50v600" />
      </svg>
      <div className="certificate-prestige-frame">
        <header className="certificate-school">
          {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
          <span>{data.institutionName}</span>
          <InstitutionAddress data={data} />
        </header>
        <div className="certificate-prestige-crest" aria-hidden="true">
          <span>✦</span>
          <small>HONOUR</small>
        </div>
        <div className="certificate-copy certificate-copy-centered">
          <p className="certificate-kicker">With highest distinction</p>
          <p className="certificate-presentation">
            The institution confers this honour upon
          </p>
          <h2>{data.recipientName}</h2>
          <RecipientType data={data} />
          <h1>{data.awardType}</h1>
          <p className="certificate-citation">
            {data.citation ||
              'For exceptional accomplishment and exemplary service to the school community.'}
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
              {data.signatoryTitle || data.principalTitle || 'Chancellor'}
            </span>
          </div>
          <div className="certificate-prestige-reference">
            <strong>{data.issuedOn}</strong>
            <span>{data.certificateNumber}</span>
          </div>
        </footer>
        <CertificateNotes data={data} />
      </div>
    </article>
  );
}
