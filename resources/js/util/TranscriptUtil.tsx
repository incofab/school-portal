import {
  AcademicSession,
  Assessment,
  ClassResultInfo,
  Classification,
  Course,
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
  private sessionSubjects: {
    [sessionId: number]: {
      [course_id: number]: Course;
    };
  } = {};
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
  getSortedTranscriptArr() {
    return Object.values(this.transcript).sort(
      (a, b) =>
        a.sessionResult.academic_session!.order_index -
        b.sessionResult.academic_session!.order_index
    );
  }
  getSessionSubjects(academic_session_id: number) {
    return this.sessionSubjects?.[academic_session_id];
  }

  private formatResult() {
    const transcript: Transcript = {} as Transcript;
    this.courseResults.map((courseResult) => {
      this.setSessionSubjects(courseResult);
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
      transcriptTerm.courseResults[courseResult.course_id] = courseResult;

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

  private setSessionSubjects(courseResult: CourseResult) {
    if (
      this.sessionSubjects?.[courseResult.academic_session_id]?.[
        courseResult.course_id
      ]
    ) {
      return;
    }
    const session =
      this.sessionSubjects[courseResult.academic_session_id] ?? {};
    session[courseResult.course_id] = courseResult.course!;
    this.sessionSubjects[courseResult.academic_session_id] = session;
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
