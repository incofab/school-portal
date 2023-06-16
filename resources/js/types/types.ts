export type Nullable<T> = T | null;

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
}

export enum InstitutionUserType {
  Admin = 'admin',
  Student = 'student',
  Teacher = 'teacher',
  Alumni = 'alumni',
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

export enum Gender {
  Male = 'male',
  Female = 'female',
}

// export enum ProgrammeType {
//   Regular = 'regular',
//   Sandwich = 'sandwich',
//   Certificate = 'certificate',
//   Postgraduate = 'postgraduate',
// }

// export enum AdmissionYear {
//   Y2023 = '2023',
//   Y2022 = '2022',
//   Y2021 = '2021',
//   Y2020 = '2020',
//   Y2019 = '2019',
//   Y2018 = '2018',
//   Y2017 = '2017',
//   Y2016 = '2016',
//   Y2015 = '2015',
//   Y2014 = '2014',
//   Y2013 = '2013',
//   Y2012 = '2012',
//   Y2011 = '2011',
//   Y2010 = '2010',
// }

export interface SelectOptionType<T = string> {
  label: string;
  value: T;
}

// export enum FeePaymentInterval {
//   // PerMonth = 'per-month',
//   PerSemester = 'per-semester',
//   PerSession = 'per-session',
//   PerYear = 'per-year',
// }

// export enum PaymentDomain {
//   Institution = 'institution',
//   Welfare = 'welfare',
// }

// export enum FeePayers {
//   Department = 'department',
//   Faculty = 'faculty',
// }
