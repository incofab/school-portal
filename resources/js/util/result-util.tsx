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
import jsPDF from 'jspdf';

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

  getGrade: function (score: number) {
    let grade = '';
    let remark = '';
    let label = '';
    if (score < 40) {
      grade = 'F';
      remark = 'Fail';
      label = '0 - 39';
    } else if (score < 45) {
      grade = 'E';
      remark = 'Poor Pass';
      label = '40 - 44';
    } else if (score < 50) {
      grade = 'D';
      remark = 'Pass';
      label = '45 - 49';
    } else if (score < 55) {
      grade = 'C6';
      remark = 'Credit';
      label = '50 - 54';
    } else if (score < 60) {
      grade = 'C4';
      remark = 'Credit';
      label = '55 - 59';
    } else if (score < 65) {
      grade = 'B3';
      remark = 'Good';
      label = '60 - 64';
    } else if (score < 70) {
      grade = 'B2';
      remark = 'Very Good';
      label = '65 - 69';
    } else if (score < 80) {
      grade = 'B1';
      remark = 'Very Good';
      label = '70 - 79';
    } else if (score < 90) {
      grade = 'A2';
      remark = 'Excellent';
      label = '80 - 89';
    } else {
      grade = 'A1';
      remark = 'Distinction';
      label = '90 - 100';
    }
    return [grade, remark, label];
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

  exportAsPdf: function (id: string, filename: string | undefined = undefined) {
    const doc = new jsPDF('portrait', 'pt', 'a4');

    const allowance = 40;
    const htmlWidth = 900 + allowance;
    const a4Width = 595;

    doc.setFont('helvetica');
    // @ts-ignore
    doc.html(document.querySelector(`#${id}`) ?? '', {
      x: 10,
      y: 10,
      html2canvas: {
        scale: a4Width / htmlWidth, // default is window.devicePixelRatio
      },
      callback: function () {
        doc.save(`${filename ?? 'result-sheet'}.pdf`);
        // window.open(doc.output('bloburl')); // to debug
      },
    });
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
