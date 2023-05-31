import { InstitutionUserType, TermType } from './types';

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
  user?: User;
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
  classification_id: number;
  code: string;
  guardian_phone: string;
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
  exam: number;
  result: number;
  grade: string;
  remark: string;
  teacher?: User;
  student?: Student;
  course?: Course;
  classification?: Classification;
  academic_session?: AcademicSession;
}

export interface ClassResultInfo extends InstitutionRow {
  // student_id: number;
  // teacher_user_id: number;
  // course_id: number;
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
  classification?: Classification;
  academicSession?: AcademicSession;
}

export interface TermResult extends InstitutionRow {
  student_id: number;
  classification_id: number;
  academic_session_id: number;
  term: TermType;
  result: number;
  average: number;
  result_max: number;
  grade: string;
  remark: string;
}

export interface SessionResult extends InstitutionRow {
  student_id: number;
  classification_id: number;
  academic_session_id: number;
  result: number;
  average: number;
  result_max: number;
  grade: string;
  remark: string;
}

export interface CourseTeacher extends Row {
  course_id: number;
  user_id: number;
  classification_id: number;
  course?: Course;
  user?: User;
  classification?: Classification;
}

// export interface Fee extends Row {
//   title: string;
//   amount: number;
//   payment_interval: string;
//   feeable_type: string;
//   feeable_id: number;
//   domain: PaymentDomain;
// }

// export interface FeePayment extends Row {
//   fee_id: number;
//   user_id: number;
//   academic_session_id: number;
//   semester: string;
//   reference: string;
// }
