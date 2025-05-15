import {
  EventType,
  Examable,
  ExamAttempt,
  Feeable,
  FeeItem,
  InstitutionUserType,
  ManagerRole,
  TermType,
  TransactionType,
  WalletType,
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
  username: string;
  is_welfare: boolean;
  gender: string;
  roles?: Role[];
  institution_user?: InstitutionUser;
  student?: Student;
}

export interface TokenUser extends Row {
  name: string;
  reference: string;
  email: string;
  phone: string;
}

export interface InstitutionGroup extends Row {
  name: string;
  credit_wallet: number;
  debt_wallet: number;
  loan_limit: number;
  website: string;
  banner: string;
  // wallet_balance: number;
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

export interface Funding extends Row {
  institution_group_id: string;
  wallet: WalletType;
  amount: number;
  previous_balance: number;
  new_balance: number;
  remark: string;
  institution_group: InstitutionGroup;
}

export interface Transaction extends InstitutionRow {
  institution_group_id: string;
  wallet: WalletType;
  type: TransactionType;
  reference: string;
  amount: number;
  bbt: number;
  bat: number;
  remark: string;
  transactionable_id: number;
  transactionable_type: string;
  institution_group?: InstitutionGroup;
}

export interface SchoolActivity extends Row {
  institution_id: string;
  title: string;
  description: string;
  institution: Institution;
}

interface InstitutionRow extends Row {
  institution_id: number;
  institution?: Institution;
}

export interface Timetable extends InstitutionRow {
  classification_id: number;
  day: number;
  start_time: string;
  end_time: string;
  actionable_type: string;
  actionable_id: number;
  actionable: Course | SchoolActivity;
  timetable_coordinators: TimetableCoordinator[];
}

export interface TimetableCoordinator extends InstitutionRow {
  institution_user_id: number;
  timetable_id: number;
  institution_user?: InstitutionUser;
  timetable?: Timetable;
}

export interface Withdrawal extends InstitutionRow {
  amount: number;
  status: string;
  bank_account?: BankAccount;
  paid_at: string;
  remark: string;
}

export interface Partner extends InstitutionRow {
  user_id: number;
  user?: User;
  commission: number;
  referral_id: number;
  referral?: Partner;
  referral_commission: number;
  wallet: number;
}

export interface Commission extends InstitutionRow {
  institution_group: InstitutionGroup;
  partner?: Partner;
  commissionable_id?: number;
  commissionable_type?: string;
  commissionable?: Transaction;
  amount: number;
}

export interface BankAccount extends InstitutionRow {
  bank_name: string;
  account_name: string;
  account_number: string;
  withdrawals_count: number;
}

export interface InstitutionUser extends InstitutionRow {
  user_id: number;
  role: InstitutionUserType;
  user?: User;
  student?: Student;
}

export interface Attendance extends InstitutionRow {
  // institution_id: number;
  institution_staff_user_id: number;
  institution_user_id: number;
  institution_user: InstitutionUser;
  remark: string;
  signed_in_at: string;
  signed_out_at: string;
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

export enum OptionLetter {
  A = 'a',
  B = 'b',
  C = 'c',
  D = 'd',
}

export interface PracticeQuestion{
  question: string;
  // a: string;
  // b: string;
  // c: string;
  // d: string;
  correct_answer: string;
  [OptionLetter]: string;
}

export interface Course extends InstitutionRow {
  title: string;
  code: string;
  category: string;
  description: string;
  sessions: CourseSession[];
  
  topics?: Topic[];
}

export interface Student extends Row {
  institution_user_id: number;
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
  height: number;
  weight: number;
  attendance_count: number;
  learning_evaluation: { [key: string]: string };
  student?: Student;
  classification?: Classification;
  academic_session?: AcademicSession;
}

export interface TermDetail extends InstitutionRow {
  academic_session_id: number;
  term: TermType;
  start_date: string;
  end_date: string;
  expected_attendance_count: number;
  for_mid_term: boolean;
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
  pin_generator_id: number;
  student_id: number;
  academic_session_id: number;
  term: string;
  term_result?: TermResult;
  pin_generator: PinGenerator;
  student?: Student;
  academic_session?: AcademicSession;
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
  term?: string;
  academic_session_id?: number;
  fee_items?: FeeItem[];
  academic_session?: AcademicSession;
  fee_categories: FeeCategory[];
}

export interface FeeCategory extends InstitutionRow {
  fee_id: number;
  feeable_type: string;
  feeable_id: number;
  fee?: Fee;
  feeable?: Feeable;
}

export interface Receipt extends InstitutionRow {
  user_id: number;
  fee_id: number;
  academic_session_id?: number;
  term?: string;
  amount: number;
  amount_paid: number;
  amount_remaining: number;
  status?: string;
  academic_session: AcademicSession;
  fee_payments?: FeePayment[];
  user?: User;
  fee?: Fee;
}

export interface FeePayment extends InstitutionRow {
  fee_id: number;
  receipt_id: number;
  amount: number;
  confirmed_by_user_id?: number;
  method?: string;
  reference?: string;
  fee?: Fee;
  receipt?: Receipt;
  confirmed_by?: User;
  payable_type: string;
  payable_id: number;
  payable?: User;
}

export interface InstitutionSetting extends InstitutionRow {
  key: string;
  value: string | any;
  display_name: string;
  type: string;
}

export interface AdmissionApplication extends InstitutionRow {
  first_name: string;
  last_name: string;
  other_names: string;
  name: string;
  application_no: string;
  phone: string;
  photo?: string;
  photo_url: string;
  email: string;
  gender: string;
  address: string;
  previous_school_attended: string;
  dob: string;
  nationality: string;
  religion: string;
  reference: string;
  lga: string;
  state: string;
  intended_class_of_admission: string;
  admission_status: string;
  admission_form_id?: number;
  admission_form?: AdmissionForm;
  application_guardians?: ApplicationGuardian[];
}

export interface AdmissionForm extends InstitutionRow {
  title: string;
  description: string;
  price: number;
  is_published: boolean;
  term?: string;
  academic_session_id?: number;
  academic_session?: AcademicSession;
}

export interface ApplicationGuardian extends Row {
  first_name: string;
  last_name: string;
  other_names: string;
  phone: string;
  email: string;
  relationship: string;
}

export interface Assessment extends InstitutionRow {
  title: string;
  raw_title: string;
  description: number;
  max?: number;
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

export interface ResultPublication extends InstitutionRow {
  institution_group_id: number;
  academic_session_id: number;
  staff_user_id: number;
  term: string;
  payment_structure: string;
  num_of_results: number;
  institution_group?: InstitutionGroup;
  academic_session?: AcademicSession;
  staff?: User;
  transaction?: Transaction;
}

export interface GuardianStudent extends InstitutionRow {
  guardian_user_id: number;
  student_id: number;
  relationship: string;
  guardian?: User;
  student?: Student;
}

export interface Association extends InstitutionRow {
  title: string;
  description: string;
}

export interface UserAssociation extends InstitutionRow {
  institution_user_id: number;
  association_id: number;
  institution_user?: InstitutionUser;
  association?: Association;
}

export interface RegistrationRequest extends Row {
  partner_user_id: number;
  reference: string;
  data: { [key: string]: string };
  institution_registered_at: string;
  institution_group_registered_at: string;
  partner: User;
}

export interface PartnerRegistrationRequest extends Row {
  first_name: string;
  last_name: string;
  other_names: string;
  phone: string;
  email: string;
  username: string;
  gender: string;
  password: string;
  referral?: Partner;
}

export interface Question extends InstitutionRow {
  question_no: number;
  question: string;
  option_a: string;
  option_b: string;
  option_c: string;
  option_d: string;
  option_e: string;
  answer: string;
  answer_meta: string;
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
  code?: string;
  starts_at: string;
  transferred_at?: string;
  expires_at?: string;
  friendly_start_date: string;
  num_of_subjects: number;
  show_corrections: boolean;
  type: EventType;
  classification_id?: number;
  classification_group_id?: number;
  event_courseables?: EventCourseable[];
  exams?: Exam[];
  classification?: Classification;
  classification_group?: ClassificationGroup;
}

export interface Assignment extends InstitutionRow {
  status: string;
  starts_at: string;
  expires_at: string;
  max_score: number;
  content: string;
  course_id: number;
  institution_user_id: number;
  user?: User;
  course?: Course;
  institution_user?: InstitutionUser;
  assignment_classifications?: AssignmentClassification[];
  classifications?: Classification[];
}

export interface AssignmentClassification extends InstitutionRow {
  assignment_id: number;
  classification_id: number;
  assignment?: Assignment;
  classification?: Classification;
}

export interface Todo {
  count: number;
  route: string;
  label: string;
}

export interface Topic extends InstitutionRow {
  title: string;
  description: string;
  course_id: string;
  institution_group_id?: number;
  classification_group_id: number;
  parent_topic_id?: number;

  course?: Course;
  classification_group?: ClassificationGroup;

  scheme_of_works?: SchemeOfWork[];
}

export interface SchemeOfWork extends InstitutionRow {
  term: string;
  topic_id: number;
  week_number: number;
  learning_objectives: string;
  resources: string;
  institution_group_id: number;
  institution_id: number;

  topic?: Topic;
  lesson_plans?: LessonPlan[];
}

export interface LessonPlan extends InstitutionRow {
  objective: string;
  activities: string;
  content: string;
  institution_group_id: number;
  institution_id: number;
  scheme_of_work_id: number;
  course_teacher_id: number;

  course_teacher?: CourseTeacher;
  lesson_note?: LessonNote;
  scheme_of_work?: SchemeOfWork;
}

export interface LessonNote extends InstitutionRow {
  title: string;
  content: string;
  status: string;
  classification_group_id: string;
  institution_group_id: string;
  topic_id: number;

  course?: Course;
  classification?: Classification;
  course_teacher?: CourseTeacher;
  lesson_plan?: LessonPlan;
}

export interface Note extends InstitutionRow {
  title: string;
  content: string;
  status: string;
}

export interface AssignmentSubmission extends InstitutionRow {
  assignment_id: number;
  student_id: number;
  answer: string;
  attachments?: Attachment[];
  score?: number;
  remark?: string;
  assignment: Assignment;
  student?: Student;
}

export interface Attachment extends InstitutionRow {
  attachment: string;
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
  examable: Examable;
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

export interface Billing extends Row {
  type: string;
  institution_group_id: string;
  payment_structure: string;
  amount: number;
  institution_group: InstitutionGroup;
}

export interface FeesToPay extends Row {
  amount_paid: number;
  amount_remaining: number;
  title: string;
  is_part_payment: boolean;
}

export interface FeeSummary extends Row {
  // receipt_type: ReceiptType;
  fees_to_pay: FeesToPay;
  total_amount_to_pay: number;
  total_amount_of_the_receipt_type: number;
}
