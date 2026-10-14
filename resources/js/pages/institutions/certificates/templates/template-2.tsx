import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template2({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-02">
      <aside className="certificate-modern-rail">
        <div className="certificate-rail-pattern" aria-hidden="true">
          <svg viewBox="0 0 160 700">
            <path d="M80 20 140 80 80 140 20 80 80 20Zm0 130 60 60-60 60-60-60 60-60Zm0 130 60 60-60 60-60-60 60-60Zm0 130 60 60-60 60-60-60 60-60Zm0 130 60 60-60 60-60-60 60-60Z" />
          </svg>
        </div>
        <span className="certificate-rail-label">
          MERIT • DISTINCTION • HONOUR
        </span>
        <div className="certificate-rail-number">{data.certificateNumber}</div>
      </aside>
      <section className="certificate-modern-content">
        <header className="certificate-school">
          {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
          <span>{data.institutionName}</span>
          <InstitutionAddress data={data} />
        </header>
        <div className="certificate-modern-rule">
          <span />
        </div>
        <div className="certificate-copy certificate-copy-left">
          <p className="certificate-kicker">This distinction is presented to</p>
          <h2>{data.recipientName}</h2>
          <RecipientType data={data} />
          <h1>{data.awardType}</h1>
          <p className="certificate-citation">
            {data.citation ||
              'For an exceptional contribution and a record of meaningful achievement.'}
          </p>
        </div>
        <div className="certificate-modern-bottom">
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
        </div>
        <CertificateNotes data={data} />
      </section>
      <svg
        className="certificate-modern-orbit"
        viewBox="0 0 300 300"
        aria-hidden="true"
      >
        <circle cx="150" cy="150" r="114" />
        <circle cx="150" cy="150" r="91" />
        <path d="M150 15v270M15 150h270M54 54l192 192M246 54 54 246" />
      </svg>
    </article>
  );
}
