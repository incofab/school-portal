import {
  ExamAttempt,
  InstitutionUserType,
  ManagerRole,
  Nullable,
  TermType,
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
  photo: string;
  photo_url: string;
  email: string;
  is_welfare: boolean;
  gender: string;
  manager_role: ManagerRole;
}

export interface TokenUser {
  user_id: string;
  name: string;
  reference: string;
  email: string;
  phone: string;
}

export interface Institution extends Row {
  user_id: number;
  uuid: string;
  code: string;
  subtitle: string;
  caption: string;
  website: string;
  name: string;
  photo: string;
  address: string;
  email: string;
  phone: string;
  status: string;
  institution_settings?: InstitutionSetting[];
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
  form_teacher_id: number;
  form_teacher?: User;
}

export interface Course extends InstitutionRow {
  title: string;
  code: string;
  category: string;
  description: string;
  sessions: CourseSession[];
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
  // first_assessment: number;
  // second_assessment: number;
  exam: number;
  result: number;
  position: number;
  grade: string;
  remark: string;
  for_mid_term: boolean;
  assessment_values: { [key: string]: number };
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
  for_mid_term: boolean;
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
  for_mid_term: boolean;
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
  for_mid_term: boolean;
  teacher_comment: string;
  principal_comment: string;
  general_comment: string;
  learning_evaluation: { [key: string]: string };
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
  classification?: Classification;
  academic_session?: AcademicSession;
  student?: Student;
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

export interface InstitutionSetting extends InstitutionRow {
  key: string;
  value: string;
  display_name: string;
  type: string;
}

export interface AdmissionApplication extends Row {
  first_name: string;
  last_name: string;
  other_names: string;
  phone: string;
  email: string;
  gender: string;
  fathers_name: string;
  mothers_name: string;
  fathers_occupation: string;
  mothers_occupation: string;
  guardian_phone: string;
  photo: string;
  address: string;
  previous_school_attended: string;
  dob: string;
  nationality: string;
  religion: string;
  reference: string;
}

export interface Assessment extends InstitutionRow {
  title: string;
  raw_title: string;
  description: number;
  max: number;
  term: string;
  for_mid_term: boolean;
  depends_on: string;
}

export interface LearningEvaluationDomain extends InstitutionRow {
  title: string;
  type: string;
  max: number;
}

export interface LearningEvaluation extends InstitutionRow {
  learning_evaluation_domain_id: number;
  title: string;
  learning_evaluation_domain?: LearningEvaluationDomain;
}

export interface Question extends InstitutionRow {
  question_no: number;
  question: string;
  option_a: string;
  option_b: string;
  option_c: string;
  option_d: string;
  option_e: string;
}

export interface CourseSession extends InstitutionRow {
  course_id: number;
  session: string;
  category: string;
  general_instruction: string;
  course?: Course;
  questions?: Question[];
}

export interface Event extends InstitutionRow {
  title: string;
  description: string;
  duration: number;
  status: number;
  starts_at: string;
  num_of_subjects: number;
  event_courseables?: EventCourseable[];
  exams?: Exam[];
}

export interface EventCourseable extends Row {
  event_id: number;
  courseable_type: string;
  courseable_id: number;
  status: string;
  event?: Event;
  courseable?: CourseSession;
}

export interface Exam extends InstitutionRow {
  event_id: number;
  student_id: number;
  external_reference: string;
  exam_no: string;
  time_remaining: number;
  start_time: string;
  pause_time: string;
  end_time: string;
  score: number;
  num_of_questions: number;
  status: string;
  student?: Student;
  event?: Event;
  exam_courseables?: ExamCourseable[];
  attempts: ExamAttempt;
}

export interface ExamCourseable extends Row {
  exam_id: number;
  courseable_type: string;
  courseable_id: number;
  status: string;
  exam?: Exam;
  courseable?: CourseSession;
}
