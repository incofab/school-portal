import { Student, TokenUser, User } from './models';

export type Nullable<T> = T | null;
export type KeyValue<T = string> = { [key: string]: T };
export type GenericUser = TokenUser | User | Student;

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
