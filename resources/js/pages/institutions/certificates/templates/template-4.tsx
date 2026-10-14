import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template4({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-04">
      <div className="certificate-architecture-side">
        <span>THE SCHOOL • THE COMMUNITY • THE FUTURE</span>
        <b>{data.issuedOn}</b>
      </div>
      <svg
        className="certificate-arch"
        viewBox="0 0 460 650"
        aria-hidden="true"
      >
        <path d="M22 630V230C22 112 115 18 230 18s208 94 208 212v400" />
        <path d="M65 630V232c0-94 74-170 165-170s165 76 165 170v398M108 630V234c0-70 54-127 122-127s122 57 122 127v396" />
        <circle cx="230" cy="232" r="18" />
        <path d="m230 194 11 27 29 2-22 18 8 28-26-16-26 16 8-28-22-18 29-2Z" />
      </svg>
      <header className="certificate-school">
        {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
        <span>{data.institutionName}</span>
        <InstitutionAddress data={data} />
      </header>
      <section className="certificate-architecture-copy">
        <p className="certificate-kicker">
          In appreciation of outstanding contribution
        </p>
        <h2>{data.recipientName}</h2>
        <RecipientType data={data} />
        <div className="certificate-architecture-award">
          <span>IS HONOURED WITH</span>
          <strong>{data.awardType}</strong>
        </div>
        <p className="certificate-citation">
          {data.citation ||
            'For dedication, initiative and a lasting positive impact.'}
        </p>
        <div className="certificate-context">
          {data.classification}
          {data.academicSession && `  —  ${data.academicSession}`}
        </div>
      </section>
      <footer className="certificate-architecture-footer">
        <div className="certificate-signatures">
          <div>
            <strong>{data.signatoryName || data.principalName || ' '}</strong>
            <span>
              {data.signatoryTitle ||
                data.principalTitle ||
                'Authorised signatory'}
            </span>
          </div>
          <div>
            <strong>{data.certificateNumber}</strong>
            <span>Honours register</span>
          </div>
        </div>
        <CertificateNotes data={data} />
      </footer>
      <div className="certificate-architecture-medallion" aria-hidden="true">
        ✦
      </div>
    </article>
  );
}
