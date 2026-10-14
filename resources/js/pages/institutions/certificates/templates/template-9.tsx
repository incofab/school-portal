import React from 'react';
import { CertificateTemplateProps } from './types';
import {
  CertificateNotes,
  InstitutionAddress,
  RecipientType,
} from './placeholders';

export default function Template9({ data }: CertificateTemplateProps) {
  return (
    <article className="certificate-design certificate-design-09">
      <svg
        className="certificate-celebration-art"
        viewBox="0 0 420 650"
        aria-hidden="true"
      >
        <circle cx="210" cy="325" r="155" />
        <circle cx="210" cy="325" r="130" />
        <circle cx="210" cy="325" r="92" />
        <path d="M210 10v170m0 290v170M0 325h55m310 0h55M48 90l120 120m84 230L372 560m0-470L252 210M168 440 48 560" />
        <path d="m210 224 26 70 74 2-59 45 21 72-62-42-62 42 21-72-59-45 74-2Z" />
        <circle cx="38" cy="202" r="8" />
        <circle cx="378" cy="170" r="6" />
        <circle cx="58" cy="440" r="5" />
        <circle cx="350" cy="486" r="9" />
        <path d="m55 300 12 12-12 12-12-12Zm306-132 10 10-10 10-10-10ZM96 92l8 8-8 8-8-8Z" />
      </svg>
      <div className="certificate-celebration-body">
        <header className="certificate-school">
          {data.institutionLogo && <img src={data.institutionLogo} alt="" />}
          <span>{data.institutionName}</span>
          <InstitutionAddress data={data} />
        </header>
        <div className="certificate-celebration-banner">
          <span>✦</span>
          <span>RECOGNITION · {data.issuedOn}</span>
          <span>✦</span>
        </div>
        <p className="certificate-presentation">A special recognition for</p>
        <h1>{data.recipientName}</h1>
        <RecipientType data={data} />
        <div className="certificate-celebration-title">
          <span>ACHIEVEMENT AWARD</span>
          <h2>{data.awardType}</h2>
        </div>
        <p className="certificate-citation">
          {data.citation ||
            'For bringing curiosity, kindness and an exceptional spirit to every challenge.'}
        </p>
        <div className="certificate-context">
          {data.classification}
          {data.academicSession && `  ·  ${data.academicSession}`}
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
            <span>Certificate reference</span>
          </div>
        </footer>
        <CertificateNotes data={data} />
      </div>
      <div className="certificate-celebration-medallion" aria-hidden="true">
        <span>✦</span>
        <small>
          BRIGHT
          <br />
          FUTURE
        </small>
      </div>
    </article>
  );
}
