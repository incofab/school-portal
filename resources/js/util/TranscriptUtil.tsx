import {
  AcademicSession,
  Assessment,
  ClassResultInfo,
  Classification,
  CourseResult,
  CourseResultInfo,
  LearningEvaluation,
  SessionResult,
  Student,
  TermResult,
} from '@/types/models';

export interface Transcript {
  [session_id: string]: TranscriptSession;
}

export interface TranscriptSession {
  sessionResult: SessionResult;
  termResultDetail: { [term: string]: TranscriptTerm };
}
export interface TranscriptTerm {
  termResult: TermResult;
  courseResults: { [courseId: number]: CourseResult };
  // courseResultInfo: { [courseId: number]: CourseResultInfo };
}

class TranscriptUtil {
  private transcript: Transcript;
  constructor(
    private student: Student,
    private courseResults: CourseResult[],
    private termResults: TermResult[],
    private sessionResults: SessionResult[]
  ) {
    this.transcript = this.formatResult();
  }

  getTranscript() {
    return this.transcript;
  }

  private formatResult() {
    const transcript: Transcript = {} as Transcript;
    this.courseResults.map((courseResult) => {
      const transcriptSession =
        transcript[courseResult.academic_session_id] ??
        ({} as TranscriptSession);

      if (!transcriptSession.sessionResult) {
        transcriptSession.sessionResult =
          this.getSessionResult(courseResult) ?? ({} as SessionResult);
      }

      const transcriptTerm =
        transcriptSession.termResultDetail?.[courseResult.term] ??
        ({} as TranscriptTerm);

      if (!transcriptTerm.termResult) {
        transcriptTerm.termResult =
          this.getTermResult(courseResult) ?? ({} as TermResult);
      }
      transcriptTerm.courseResults = transcriptTerm.courseResults ?? {};
      transcriptTerm.courseResults[courseResult.id] = courseResult;

      // build
      transcript[courseResult.academic_session_id] = {
        ...transcriptSession,
        termResultDetail: {
          ...transcriptSession.termResultDetail,
          [courseResult.term]: transcriptTerm,
        },
      };
    });
    return transcript;
  }

  getTermResult(courseResult: CourseResult) {
    return this.termResults.find(
      (termResult) =>
        termResult.academic_session_id === courseResult.academic_session_id &&
        termResult.term === courseResult.term &&
        termResult.student_id === courseResult.student_id &&
        courseResult.classification_id === termResult.classification_id
    );
  }
  getSessionResult(courseResult: CourseResult) {
    return this.sessionResults.find(
      (sessionResult) =>
        sessionResult.academic_session_id ===
          courseResult.academic_session_id &&
        sessionResult.student_id === courseResult.student_id &&
        courseResult.classification_id === sessionResult.classification_id
    );
  }
}

export default TranscriptUtil;

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
