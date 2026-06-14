import {
  AdmissionApplication,
  RecruitmentApplication,
  Student,
  TokenUser,
  User,
} from '@/types/models';

export class TokenUserUtil {
  private name: string | null;
  public isAdmissionApplication = false;
  public isRecruitmentApplication = false;
  public isStudent = false;
  public isUser = false;
  public isTokenUser = false;
  constructor(
    private tokenUser:
      | TokenUser
      | Student
      | User
      | AdmissionApplication
      | RecruitmentApplication
      | null
  ) {
    if (tokenUser === null) {
      this.name = null;
    } else if (
      'application_no' in tokenUser &&
      'vacancy_post_id' in tokenUser
    ) {
      this.isRecruitmentApplication = true;
      this.name = (tokenUser as RecruitmentApplication).name;
    } else if ('application_no' in tokenUser) {
      this.isAdmissionApplication = true;
      this.name = (tokenUser as AdmissionApplication).name;
    } else if ('name' in tokenUser) {
      // TokenUser
      this.isTokenUser = true;
      this.name = (tokenUser as TokenUser).name;
    } else if ('full_name' in tokenUser) {
      // User
      this.isUser = true;
      this.name = (tokenUser as User).full_name;
    } else {
      this.isStudent = true;
      this.name = (tokenUser as Student).user?.full_name ?? '';
    }
  }
  getClassName() {
    return this.isStudent
      ? (this.tokenUser as Student).classification?.title ?? ''
      : '';
  }
  getName() {
    return this.name;
  }
}

export default function tokenUserUtil(
  tokenUser:
    | TokenUser
    | Student
    | User
    | AdmissionApplication
    | RecruitmentApplication
    | null
) {
  return new TokenUserUtil(tokenUser);
}
