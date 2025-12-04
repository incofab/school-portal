import useSharedProps from '@/hooks/use-shared-props';
import {
  AcademicSession,
  Assessment,
  ClassResultInfo,
  Classification,
  CourseResult,
  CourseResultInfo,
  LearningEvaluation,
  ResultCommentTemplate,
  Student,
  TermDetail,
  TermResult,
} from '@/types/models';
import {
  PositionDisplayType,
  ResultCommentTemplateType,
  ResultSettingType,
} from '@/types/types';
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
    if (position >= 11 && position <= 20) {
      suffix = 'th';
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

  getGrade: function (
    score: number,
    resultCommentTemplate?: ResultCommentTemplate[]
  ) {
    let grade = '';
    let pointsGrade = 0;
    let remark = '';
    let range = '';

    if (score < 40) {
      grade = 'F';
      remark = 'Progressing';
      range = '1.0% - 39.0%';
      pointsGrade = 0;
    } else if (score < 50) {
      grade = 'E';
      remark = 'Fair';
      range = '40.0% - 49.0%';
      pointsGrade = 2;
    } else if (score < 60) {
      grade = 'D';
      remark = 'Pass';
      range = '50.0% - 59.0%';
      pointsGrade = 3;
    } else if (score < 70) {
      grade = 'C';
      remark = 'Good';
      range = '60.0% - 69.0%';
      pointsGrade = 4;
    } else if (score < 90) {
      grade = 'B';
      remark = 'Very Good';
      range = '70.0% - 89.0%';
      pointsGrade = 4;
    } else {
      grade = 'A';
      remark = 'Excellent';
      range = '90.0% - Above';
      pointsGrade = 5;
    }

    const comment = ResultUtil.getCommentFromTemplate(
      score,
      resultCommentTemplate
    );

    if (comment) {
      grade = comment.grade;
      remark = comment.grade_label;
      range = `${comment.min} - ${comment.max}`;
    }
    return { grade, remark, range, pointsGrade };
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
      ([key, val]) => (total += Number(val))
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

  //https://stackoverflow.com/questions/18191893/generate-pdf-from-html-in-div-using-javascript#:~:text=No%20depenencies%2C%20pure%20JS
  /*
  function printDiv({divId, title}) {
    let mywindow = window.open('', 'PRINT', 'height=650,width=900,top=100,left=150');
  
    mywindow.document.write(`<html><head><title>${title}</title>`);
    mywindow.document.write('</head><body >');
    mywindow.document.write(document.getElementById(divId).innerHTML);
    mywindow.document.write('</body></html>');
  
    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10
  
    mywindow.print();
    mywindow.close();
  
    return true;
  }
  */

  getCommentFromTemplate: function (
    score: number,
    commentTemplate?: ResultCommentTemplate[]
  ) {
    if (!commentTemplate) {
      return undefined;
    }
    score = Math.round(score);
    const comment = commentTemplate.find(
      (item) => Number(item.min) <= score && Number(item.max) >= score
    );
    return comment;
  },

  /** This should be removed next term. Grades should not be shown in the list-course-results */
  filterTemplates: function (
    allResultComments: ResultCommentTemplate[],
    classificationId: number,
    forMidTerm: boolean
  ) {
    const filteredResultComments = [];
    for (let i = 0; i < allResultComments.length; i++) {
      const comment = allResultComments[i];
      if (comment.type) {
        if (
          forMidTerm &&
          comment.type === ResultCommentTemplateType.MidTermResult
        ) {
          filteredResultComments.push(comment);
        } else if (
          !forMidTerm &&
          comment.type === ResultCommentTemplateType.FullTermResult
        ) {
          filteredResultComments.push(comment);
        } else {
          continue;
        }
      }
      if ((comment.classifications?.length ?? 0) === 0) {
        filteredResultComments.push(comment);
        continue;
      }
      for (let j = 0; j < comment.classifications!.length; j++) {
        const classification = comment.classifications![j];
        if (classification.id === classificationId) {
          filteredResultComments.push(comment);
          break;
        }
      }
    }
    return filteredResultComments;
  },
};

export default ResultUtil;

export function useResultSetting() {
  const { resultSetting } = useSharedProps();
  const hidePosition =
    resultSetting[ResultSettingType.PositionDisplayType] ===
    PositionDisplayType.Hidden;
  const showGrade =
    resultSetting[ResultSettingType.PositionDisplayType] ===
    PositionDisplayType.Grade;
  const showPosition =
    resultSetting[ResultSettingType.PositionDisplayType] ===
    PositionDisplayType.Position;

  return { hidePosition, showGrade, showPosition };
}

export interface ResultProps {
  termResult: TermResult;
  courseResults: CourseResult[];
  classResultInfo: ClassResultInfo;
  courseResultInfoData: { [course_id: string | number]: CourseResultInfo };
  academicSession: AcademicSession;
  classification: Classification;
  student: Student;
  assessments: Assessment[];
  learningEvaluations: LearningEvaluation[];
  resultCommentTemplate: ResultCommentTemplate[];
  termDetail: TermDetail;
  signed_url: string;
}
