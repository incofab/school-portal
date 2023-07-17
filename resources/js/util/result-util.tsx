import {
  AcademicSession,
  Assessment,
  ClassResultInfo,
  Classification,
  CourseResult,
  CourseResultInfo,
  LearningEvaluation,
  Student,
  TermResult,
} from '@/types/models';
import { Text } from '@chakra-ui/react';

const ResultUtil = {
  getPositionSuffix: function (position: number) {
    const lastChar = position % 10;
    let suffix = '';
    switch (lastChar) {
      case 1:
        suffix = 'st';
        break;
      case 2:
        suffix = 'nd';
        break;
      case 3:
        suffix = 'rd';
        break;
      default:
        suffix = 'th';
        break;
    }
    return suffix;
  },

  formatPosition: function (position: number) {
    return (
      <>
        {position}
        <sup>{this.getPositionSuffix(position)}</sup>
      </>
    );
  },

  getRemark: function (grade: string) {
    switch (grade) {
      case 'A':
        return 'Excellent';
      case 'B':
        return 'Very Good';
      case 'C':
        return 'Good';
      case 'D':
        return 'Fair';
      case 'E':
        return 'Poor';
      case 'F':
        return 'Failed';
      default:
        return 'Unknown';
    }
  },

  getClassSection: function (classTitle: string) {
    classTitle = classTitle.toLowerCase().replaceAll(' ', '');
    if (classTitle.indexOf('ss') >= 0 || classTitle.indexOf('ss') >= 0) {
      return 'Senior Secondary Section';
    } else if (
      classTitle.indexOf('js') >= 0 ||
      classTitle.indexOf('j.s') >= 0
    ) {
      return 'Junior Secondary Section';
    } else if (classTitle.indexOf('primary')) {
      return 'Primary Section';
    } else {
      return 'School Section';
    }
  },

  VerticalText: function ({ text }: { text: string }) {
    return <Text className="vertical-header">{text}</Text>;
  },

  getAssessmentScore: function (courseResult: CourseResult) {
    let total = 0;
    Object.entries(courseResult.assessment_values).map(
      ([key, val]) => (total += val)
    );
    return total;
  },
};

export default ResultUtil;

export interface ResultProps {
  termResult: TermResult;
  courseResults: CourseResult[];
  classResultInfo: ClassResultInfo;
  courseResultInfoData: { [key: string | number]: CourseResultInfo };
  academicSession: AcademicSession;
  classification: Classification;
  student: Student;
  assessments: Assessment[];
  learningEvaluations: LearningEvaluation[];
}
