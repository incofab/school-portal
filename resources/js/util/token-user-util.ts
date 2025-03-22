import { Student, TokenUser, User } from '@/types/models';

export class TokenUserUtil {
  private name: string | null;
  public isStudent = false;
  public isUser = false;
  public isTokenUser = false;
  constructor(private tokenUser: TokenUser | Student | User | null) {
    if (tokenUser == null) {
      this.name = null;
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
  tokenUser: TokenUser | Student | User | null
) {
  return new TokenUserUtil(tokenUser);
}
