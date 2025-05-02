// import { useColorModeValue } from '@chakra-ui/react';
import {
  AdmissionApplication,
  Classification,
  ClassificationGroup,
  Institution,
  Student,
  TokenUser,
  User,
} from './models';

export type Nullable<T> = T | null;
export type KeyValue<T = string> = { [key: string]: T };
export type GenericUser = TokenUser | User | Student | AdmissionApplication;
export type Feeable = Classification | ClassificationGroup | Institution;
export type Examable = GenericUser;

// export const bgWhite = useColorModeValue('white', 'gray.900');
// export const bgBrand = useColorModeValue('brand.50', 'gray.800');
// export const bgGreen = useColorModeValue('green.50', 'gray.800');

export interface PaginationResponse<T> {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  first_page_url: string;
  last_page_url: string;
  next_page_url: string;
  prev_page_url: string;
  path: string;
  from: number;
  to: number;
  data: T[];
}

export interface Message {
  error: string;
  success: string;
}

export enum ManagerRole {
  Admin = 'admin',
  Partner = 'partner',
}

export enum NotificationChannelsType {
  Email = 'email',
  Sms = 'sms',
}

export enum NotificationReceiversType {
  AllClasses = 'all-classes',
  SpecificClass = 'specific-class',
}

export enum InstitutionUserType {
  Admin = 'admin',
  Student = 'student',
  Accountant = 'accountant',
  Teacher = 'teacher',
  Alumni = 'alumni',
  Guardian = 'guardian',
}

export enum UserRoleType {
  Admin = 'admin',
  Student = 'student',
  Alumni = 'alumni',
}

export enum TermType {
  First = 'first',
  Second = 'second',
  Third = 'third',
}

export enum WalletType {
  Credit = 'credit',
  Debt = 'debt',
}

export enum TransactionType {
  Credit = 'credit',
  Debit = 'debit',
}

export enum FullTermType {
  FirstMid = 'first-mid',
  First = 'first',
  SecondMid = 'second-mid',
  Second = 'second',
  ThirdMid = 'third-mid',
  Third = 'third',
}

export enum Gender {
  Male = 'male',
  Female = 'female',
}

export enum WeekDay {
  Monday = '0',
  Tuesday = '1',
  Wednesday = '2',
  Thursday = '3',
  Friday = '4',
  Saturday = '5',
  Sunday = '6',
}

export enum Attendance {
  In = 'in',
  Out = 'out',
}

export enum GuardianRelationship {
  Parent = 'parent',
  Sibling = 'sibling',
  Guardian = 'guardian',
  Nibling = 'nibling',
  Pibling = 'pibling',
}

export enum LearningEvaluationDomainType {
  Text = 'text',
  Number = 'number',
  YesOrNo = 'yes-or-no',
}

export interface SelectOptionType<T = string> {
  label: string;
  value: T;
}

export enum FeePaymentInterval {
  OneTime = 'one-time',
  Termly = 'termly',
  Sessional = 'sessional',
}

export enum InstitutionSettingType {
  Result = 'result',
  CurrentTerm = 'current-term',
  CurrentAcademicSession = 'current-academic-session',
  UsesMidTermResult = 'uses-mid-term-result',
  CurrentlyOnMidTerm = 'currently-on-mid-term',
  Stamp = 'stamp',
  PaymentKeys = 'payment-keys',
}

export enum ResultSettingType {
  Template = 'template',
  PositionDisplayType = 'position-display-type',
}

export enum PositionDisplayType {
  Hidden = 'hidden',
  Position = 'position',
  Grade = 'grade',
}

export enum ResultTemplate {
  Template1 = 'template-1',
  Template2 = 'template-2',
  Template3 = 'template-3',
  Template4 = 'template-4',
  Template5 = 'template-5',
  Template6 = 'template-6',
}

export enum Religion {
  Christianity = 'christianity',
  Islam = 'islam',
  Others = 'others',
}

export enum EventStatus {
  Active = 'active',
  Ended = 'ended',
}

export enum EventType {
  StudentTest = 'student-test',
  AdmissionExam = 'admission-exam',
  RecruitmentTest = 'recruitment-test',
}

export enum ExamStatus {
  Active = 'active',
  Ended = 'ended',
  Pending = 'pending',
  Paused = 'paused',
}

export enum Nationality {
  Nigeria = 'nigeria',
  Others = 'others',
}

export enum ResultCommentTemplateType {
  All = '',
  MidTermResult = 'mid-term-result',
  FullTermResult = 'term-result',
  SessionResult = 'session-result',
}

export enum Grade {
  A = 'A',
  B = 'B',
  C = 'C',
  D = 'D',
  E = 'E',
  F = 'F',
}

export enum PriceType {
  ResultChecking = 'result-checking',
  EmailSending = 'email-sending',
  SmsSending = 'sms-sending',
}

export enum PaymentStructure {
  PerTerm = 'per-term',
  PerSession = 'per-session',
  PerStudentPerTerm = 'per-student-per-term',
  PerStudentPerSession = 'per-student-per-session',
}

export enum NoteStatusType {
  Draft = 'draft',
  Published = 'published',
}

export enum TimetableActionableType {
  Course = 'course',
  SchoolActivity = 'school-activity',
}

export enum AdmissionStatusType {
  Pending = 'pending',
  Admitted = 'admitted',
  Declined = 'declined',
}

export enum WithdrawalStatusType {
  // Pending = 'pending',
  Paid = 'paid',
  Declined = 'declined',
}

export interface ExamAttempt {
  [questionId: string | number]: string;
}

export interface BreadCrumbParam {
  title: string;
  href?: string;
}

export interface PaymentKey {
  private_key: string;
  public_key: string;
}

export interface TimetableCell {
  id?: number;
  day: number;
  start_time: string;
  end_time: string;
  actionable_type?: string;
  actionable_id?: number;
  actionable_name?: string;
  coordinators?: {
    coordinator_user_id?: number;
    coordinator_name?: string;
  }[];
}

export interface FeeItem {
  title: string;
  amount: number;
}

export enum FeeCategoryType {
  Institution = 'institution',
  Classification = 'classification',
  ClassificationGroup = 'classification-group',
  Association = 'association',
}
