export interface DashboardData {
  num_subjects: number;
  num_students: number;
  num_staff: number;
  num_classes: number;
  student_population_year_growth: { year: string; count: number }[];
  student_population_month_growth: { month: string; count: number }[];
  gender_distribution: { gender: string; count: number }[];
  fee_payments: { month: string; total: number }[];
  students_per_class: {
    title: string;
    students_count: number;
    male_students_count: number;
    female_students_count: number;
  }[];
}
