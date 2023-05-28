import {
  AdmissionYear,
  InstitutionUserType,
  PaymentDomain,
  ProgrammeType,
  TermType,
  UserRoleType,
} from './types';

export interface Row {
  id: number;
  created_at: string;
  updated_at: string;
}

export interface AcademicSession extends Row {
  title: string;
}

export interface User extends Row {
  first_name: string;
  last_name: string;
  other_names: string;
  full_name: string;
  phone: string;
  email: string;
  is_welfare: boolean;
}

export interface Institution extends Row {
  user_id: number;
  uuid: string;
  code: string;
  name: string;
  photo: string;
  address: string;
  email: string;
  phone: string;
  status: string;
}

interface InstitutionRow extends Row {
  institution_id: number;
  institution?: Institution;
}

export interface InstitutionUser extends InstitutionRow {
  user_id: number;
  role: InstitutionUserType;
}

export interface Classification extends InstitutionRow {
  title: string;
  description: string;
}

export interface Course extends InstitutionRow {
  title: string;
  code: string;
  category: string;
  description: string;
}

export interface Student extends Row {
  user_id: string;
  code: string;
  classification_id: string;
  classification?: Classification;
  user?: User;
}

export interface CourseResult extends InstitutionRow {
  student_id: number;
  teacher_user_id: number;
  course_id: number;
  classification_id: number;
  academic_session_id: number;
  term: TermType;
  first_assessment: number;
  second_assessment: number;
  result: number;
  grade: string;
  remark: string;
}

export interface ClassResultInfo extends InstitutionRow {
  student_id: number;
  teacher_user_id: number;
  course_id: number;
  classification_id: number;
  academic_session_id: number;
  term: TermType;
  num_of_students: number;
  num_of_courses: number;
  total_score: number;
  max_obtainable_score: number;
  max_score: number;
  min_score: number;
  average: number;
}

export interface Hostel extends Row {
  title: string;
  apartment_name: string;
  capacity: number;
  num_of_users: number;
}

export interface LecturerCourse extends Row {
  course_id: number;
  user_id: number;
  programme: ProgrammeType;
  course?: Course;
  user?: User;
}

export interface Fee extends Row {
  title: string;
  amount: number;
  payment_interval: string;
  feeable_type: string;
  feeable_id: number;
  domain: PaymentDomain;
}

export interface FeePayment extends Row {
  fee_id: number;
  user_id: number;
  academic_session_id: number;
  semester: string;
  reference: string;
}
