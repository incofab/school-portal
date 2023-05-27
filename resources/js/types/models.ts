import {
  AdmissionYear,
  PaymentDomain,
  ProgrammeType,
  UserRoleType,
} from './types';

export interface Row {
  id: number;
  created_at: string;
  updated_at: string;
}

export interface Course extends Row {
  title: string;
  code: string;
  credit_unit: number;
}

export interface Faculty extends Row {
  title: string;
}

export interface Department extends Row {
  title: string;
  faculty_id: number;
}

export interface Hostel extends Row {
  title: string;
  apartment_name: string;
  capacity: number;
  num_of_users: number;
}

export interface HostelUser extends Row {
  user_id: number;
  hostel_id: number;
  active: boolean;
}

export interface CourseRegistration extends Row {
  user_id: number;
  course_id: number;
  academic_session_id: number;
  semester: string;
}

export interface CourseResult extends Row {
  course_registration_id: number;
  result: number;
  result_max: number;
  grade: string;
  remark: string;
}

export interface User extends Row {
  first_name: string;
  last_name: string;
  other_names: string;
  full_name: string;
  phone: string;
  email: string;
  role: UserRoleType;
  is_welfare: boolean;
}

export interface Student extends Row {
  user_id: string;
  reg_no: string;
  department_id: string;
  admission_year: AdmissionYear;
  programme: ProgrammeType;
}

export interface AcademicSession extends Row {
  title: string;
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
