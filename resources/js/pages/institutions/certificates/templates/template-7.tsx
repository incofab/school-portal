import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template7({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-07">
      <div className="certificate-medal-side">
        <svg
          className="certificate-medal-ribbon"
          viewBox="0 0 230 590"
          aria-hidden="true"
        >
          <path d="m47 298 8 247 60-48 59 48 8-247" />
          <path d="M115 20 137 76l61 5-47 39 15 60-51-32-51 32 15-60-47-39 61-5Z" />
          <circle cx="115" cy="117" r="85" />
          <circle cx="115" cy="117" r="63" />
          <path d="m115 76 12 26 29 2-22 19 7 28-26-15-26 15 7-28-22-19 29-2Z" />
        </svg>
        <span>MERIT</span>
      </div>
      <div className="certificate-medal-content">
        <header className="certificate-school">
          {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
          <span>{data.institutionName}</span>
          <InstitutionAddress data={data} />
        </header>
        <div className="certificate-copy certificate-copy-left">
          <p className="certificate-kicker">Awarded in recognition of</p>
          <h2>{data.recipientName}</h2>
          <RecipientType data={data} />
          <h1>{data.awardType}</h1>
          <p className="certificate-citation">
            {data.citation ||
              'A milestone achieved through focus, resilience and dedication.'}
          </p>
        </div>
        <div className="certificate-medal-meta">
          <span>{data.classification || 'Institution-wide'}</span>
          {data.academicSession && <span>{data.academicSession}</span>}
          <span>{data.issuedOn}</span>
        </div>
        <footer className="certificate-signatures">
          <div>
            <strong>{data.signatoryName || data.principalName || ' '}</strong>
            <span>
              {data.signatoryTitle ||
                data.principalTitle ||
                'School leadership'}
            </span>
          </div>
          <div>
            <strong>{data.certificateNumber}</strong>
            <span>Honours register</span>
          </div>
        </footer>
        <CertificateNotes data={data} />
      </div>
      <svg
        className="certificate-medal-stars"
        viewBox="0 0 180 600"
        aria-hidden="true"
      >
        <path d="m55 50 8 17 19 2-14 12 4 19-17-10-17 10 4-19-14-12 19-2Zm65 180 6 13 15 1-11 10 3 15-13-8-13 8 3-15-11-10 15-1Zm-44 202 6 13 15 1-11 10 3 15-13-8-13 8 3-15-11-10 15-1Z" />
      </svg>
    </article>
  );
}
