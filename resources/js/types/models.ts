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
  order_index: number;
}

export interface Role extends Row {
  name: ManagerRole;
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
  roles?: Role[];
  institution_user?: InstitutionUser;
}

export interface TokenUser extends Row {
  name: string;
  reference: string;
  email: string;
  phone: string;
}

export interface InstitutionGroup extends Row {
  name: string;
}
export interface Institution extends Row {
  user_id: number;
  institution_group_id: number;
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
  initials: string;
  institution_settings?: InstitutionSetting[];
  institution_group: InstitutionGroup;
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
  classification_group_id: number;
  classification_group?: ClassificationGroup;
}

export interface ClassificationGroup extends InstitutionRow {
  title: string;
  classifications_count?: number;
}

export interface StudentClassMovement extends InstitutionRow {
  user_id: number;
  source_classification_id: number;
  destination_classification_id: number;
  academic_session_id: number;
  student_id: number;
  revert_reference_id: number;
  term: string;
  batch_no: string;
  reason: string;
  note: string;
  source_class?: Classification;
  destination_class?: Classification;
  student?: Student;
  user?: User;
  academic_session?: AcademicSession;
  revert_reference?: StudentClassMovement;
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
  full_code: string;
  guardian_phone: string;
  classification?: Classification;
  user?: User;
  course_results?: CourseResult[];
  guardian?: User;
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
  next_term_resumption_date: string;
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
  student_id: number;
  academic_session_id: number;
  term: string;
  term_result?: TermResult;
  pin_print?: PinPrint;
  pin_generator: PinGenerator;
  student?: Student;
  academic_session?: AcademicSession;
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

export interface ReceiptType extends InstitutionRow {
  title: string;
  descriptions: string;
}

export interface Fee extends InstitutionRow {
  title: string;
  amount: number;
  payment_interval: string;
  receipt_type_id: number;
  classification_group_id?: number;
  classification_id?: number;
  receipt_type?: ReceiptType;
  classification?: Classification;
  classification_group?: ClassificationGroup;
}

export interface Receipt extends InstitutionRow {
  receipt_type_id: number;
  user_id: number;
  academic_session_id?: number;
  term?: string;
  reference: string;
  title: string;
  classification_id: number;
  classification_group_id: number;
  total_amount: number;
  approved_at: string;
  approved_by_user_id: number;
  receipt_type?: ReceiptType;
  user?: User;
  academic_session: AcademicSession;
  classification?: Classification;
  classification_group?: ClassificationGroup;
  fee_payments?: FeePayment[];
}

export interface FeePayment extends InstitutionRow {
  fee_id: number;
  user_id: number;
  receipt_id: number;
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
  method?: string;
  transaction_reference?: string;
  fee_payment?: FeePayment;
  confirmed_by?: User;
}

export interface InstitutionSetting extends InstitutionRow {
  key: string;
  value: string | any;
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
  lga: string;
  state: string;
  intended_class_of_admission: string;
  fathers_phone: string;
  fathers_email: string;
  fathers_residential_address: string;
  fathers_office_address: string;
  mothers_phone: string;
  mothers_email: string;
  mothers_residential_address: string;
  mothers_office_address: string;
  admission_status: string;
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

export interface ResultCommentTemplate extends InstitutionRow {
  comment: string;
  comment_2: string;
  grade: string;
  grade_label: string;
  type: string;
  min: number;
  max: number;
}

export interface GuardianStudent extends InstitutionRow {
  guardian_user_id: number;
  student_id: number;
  relationship: string;
  guardian?: User;
  student?: Student;
}

export interface RegistrationRequest extends Row {
  partner_user_id: number;
  reference: string;
  data: { [key: string]: string };
  institution_registered_at: string;
  institution_group_registered_at: string;
  partner: User;
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
  status: string;
  starts_at: string;
  friendly_start_date: string;
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
  examable_type: string;
  examable_id: number;
  examable: TokenUser | Student;
  event?: Event;
  exam_courseables?: ExamCourseable[];
  attempts: ExamAttempt;
}

export interface ExamCourseable extends Row {
  exam_id: number;
  courseable_type: string;
  courseable_id: number;
  status: string;
  score: number;
  num_of_questions: number;
  exam?: Exam;
  courseable?: CourseSession;
}
