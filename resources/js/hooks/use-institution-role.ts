import useSharedProps from './use-shared-props';
import { InstitutionUserType } from '@/types/types';

export default function useInstitutionRole() {
  const { currentInstitutionUser } = useSharedProps();

  const instRole = currentInstitutionUser.role;

  const forTeacher =
    instRole === InstitutionUserType.Admin ||
    instRole === InstitutionUserType.Teacher;

  const forAccountant =
    instRole === InstitutionUserType.Admin ||
    instRole === InstitutionUserType.Accountant;

  const isAdmin = instRole === InstitutionUserType.Admin;
  const isStudent = instRole === InstitutionUserType.Student;
  const isAlumni = instRole === InstitutionUserType.Alumni;
  const isGuardian = instRole === InstitutionUserType.Guardian;
  const isAccountant = instRole === InstitutionUserType.Accountant;
  const isTeacher = instRole === InstitutionUserType.Teacher;
  const isStaff =
    instRole !== InstitutionUserType.Student &&
    instRole !== InstitutionUserType.Alumni;

  return {
    forTeacher,
    forAccountant,
    isAdmin,
    isStudent,
    isAlumni,
    isGuardian,
    isAccountant,
    isTeacher,
    isStaff,
  };
}
