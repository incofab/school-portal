export interface CertificateArtworkData {
  institutionName: string;
  institutionLogo?: string | null;
  primaryColor: string;
  recipientName: string;
  recipientType?: string | null;
  awardType: string;
  citation?: string | null;
  issuedOn: string;
  academicSession?: string | null;
  classification?: string | null;
  signatoryName?: string | null;
  signatoryTitle?: string | null;
  certificateNumber: string;
  schoolMotto?: string | null;
  schoolAddress?: string | null;
  principalName?: string | null;
  principalTitle?: string | null;
  customFooter?: string | null;
  revoked?: boolean;
}

export interface CertificateTemplateProps {
  data: CertificateArtworkData;
}
