import { Student, TokenUser, User } from '@/types/models';

export class TokenUserUtil {
  private name: string | null;
  constructor(tokenUser: TokenUser | Student | User | null) {
    if (tokenUser == null) {
      this.name = null;
    } else if ('name' in tokenUser) {
      // TokenUser
      this.name = (tokenUser as TokenUser).name;
    } else if ('full_name' in tokenUser) {
      // User
      this.name = (tokenUser as User).full_name;
    } else {
      this.name = (tokenUser as Student).user?.full_name ?? '';
    }
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
