import { InstitutionUserType, ManagerRole, Nullable, TermType } from './types';

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
  photo: string;
  photo_url: string;
  email: string;
  is_welfare: boolean;
  gender: string;
  manager_role: ManagerRole;
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
  student?: Student;
}

export interface Classification extends InstitutionRow {
  title: string;
  description: string;
  has_equal_subjects: boolean;
}

export interface Course extends InstitutionRow {
  title: string;
  code: string;
  category: string;
  description: string;
}

export interface Student extends Row {
  user_id: number;
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
  position: number;
  grade: string;
  remark: string;
  teacher?: User;
  student?: Student;
  course?: Course;
  classification?: Classification;
  academic_session?: AcademicSession;
}

export interface CourseResultInfo extends InstitutionRow {
  course_id: number;
  classification_id: number;
  academic_session_id: number;
  term: TermType;
  total_score: number;
  max_obtainable_score: number;
  max_score: number;
  min_score: number;
  average: number;
  course?: Course;
  classification?: Classification;
  academic_session?: AcademicSession;
}

export interface ClassResultInfo extends InstitutionRow {
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
  academic_session?: AcademicSession;
}

export interface TermResult extends InstitutionRow {
  student_id: number;
  classification_id: number;
  academic_session_id: number;
  term: TermType;
  total_score: number;
  position: number;
  average: number;
  remark: string;
  student?: Student;
  classification?: Classification;
  academic_session?: AcademicSession;
}

export interface PinGenerator extends InstitutionRow {
  user_id: number;
  num_of_pins: number;
  reference: string;
  comment: string;
  user?: User;
}

export interface Pin extends InstitutionRow {
  pin: string;
  used_at: string;
  term_result_id: number;
  pin_print_id: number;
  pin_generator_id: number;
  term_result?: TermResult;
  pin_print?: PinPrint;
  pin_generator: PinGenerator;
}

export interface PinPrint extends InstitutionRow {
  user_id: number;
  num_of_pins: number;
  reference: string;
  comment: string;
  user?: User;
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

export interface Fee extends InstitutionRow {
  title: string;
  amount: number;
  payment_interval: string;
}

export interface FeePayment extends Row {
  fee_id: number;
  user_id: number;
  academic_session_id: number;
  term: string;
  fee_amount: number;
  amount_paid: number;
  amount_remaining: number;
  fee?: Fee;
  user?: User;
  academic_session: AcademicSession;
  fee_payment_tracks?: FeePaymentTrack[];
}

export interface FeePaymentTrack extends Row {
  fee_payment_id: number;
  confirmed_by_user_id: number;
  amount: number;
  reference: string;
  method: string;
  feePayment?: FeePayment;
  confirmed_by?: User;
}
