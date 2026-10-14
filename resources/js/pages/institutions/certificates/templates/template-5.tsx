import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template5({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-05">
      <aside className="certificate-editorial-index">
        <span>EDU</span>
        <b>№ {data.certificateNumber}</b>
        <span>EST.</span>
      </aside>
      <div className="certificate-editorial-main">
        <header className="certificate-school">
          {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
          <span>{data.institutionName}</span>
          <InstitutionAddress data={data} />
        </header>
        <div className="certificate-editorial-heading">
          <span>RECOGNITION / {data.issuedOn}</span>
          <svg viewBox="0 0 120 24" aria-hidden="true">
            <path d="M1 12h48m22 0h48M58 2l10 10-10 10L48 12Z" />
          </svg>
        </div>
        <div className="certificate-editorial-copy">
          <p className="certificate-presentation">
            This distinction is awarded to
          </p>
          <h2>{data.recipientName}</h2>
          <RecipientType data={data} />
          <h1>{data.awardType}</h1>
          <p className="certificate-citation">
            {data.citation ||
              'For extraordinary effort and the thoughtful pursuit of excellence.'}
          </p>
        </div>
        <footer className="certificate-editorial-footer">
          <div className="certificate-context">
            {data.classification}
            {data.academicSession && `  /  ${data.academicSession}`}
          </div>
          <div className="certificate-signatures">
            <div>
              <strong>{data.signatoryName || data.principalName || ' '}</strong>
              <span>
                {data.signatoryTitle ||
                  data.principalTitle ||
                  'School leadership'}
              </span>
            </div>
            <div>
              <strong>{data.issuedOn}</strong>
              <span>Date awarded</span>
            </div>
          </div>
          <CertificateNotes data={data} />
        </footer>
      </div>
      <svg
        className="certificate-editorial-graphic"
        viewBox="0 0 320 320"
        aria-hidden="true"
      >
        <circle cx="160" cy="160" r="130" />
        <circle cx="160" cy="160" r="103" />
        <circle cx="160" cy="160" r="76" />
        <path d="M160 0v320M0 160h320M47 47l226 226M273 47 47 273" />
        <circle cx="160" cy="160" r="12" />
      </svg>
    </article>
  );
}
