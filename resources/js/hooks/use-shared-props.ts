import { Message } from '@/types/types';
import { Institution, InstitutionUser, User } from '@/types/models';
import { usePage } from '@inertiajs/inertia-react';

export interface SharedProps {
  shared__currentUser: User;
  shared__currentInstitution: Institution;
  shared__currentInstitutionUser: InstitutionUser;
  shared__currentAcademicSession: number;
  shared__currentTerm: string;
  shared__isImpersonating: boolean;
  shared__csrfToken: string;
  shared__message: Message;
}

export default function useSharedProps() {
  const page = usePage();
  const props = page.props as unknown as SharedProps;

  return {
    currentUser: props.shared__currentUser as User,
    isImpersonating: props.shared__isImpersonating as boolean,
    csrfToken: props.shared__csrfToken as string,
    message: props.shared__message as Message,
    currentInstitution: props.shared__currentInstitution,
    currentInstitutionUser: props.shared__currentInstitutionUser,
    currentTerm: props.shared__currentTerm,
    currentAcademicSession: props.shared__currentAcademicSession,
  };
}
