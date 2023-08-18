export interface ExamAttempt {
  [questionId: string | number]: {
    attempt: string;
  };
}
export interface ExamTab {
  currentQuestionIndex: number;
  exam_courseable_id: number;
}

class ExamUtil {
  private attemptManager: AttemptManager;
  private tabManager: TabManager;
  constructor(private reRender: () => void) {
    this.attemptManager = new AttemptManager(this.reRender);
    this.tabManager = new TabManager(this.reRender);
  }
  getAttemptManager() {
    return this.attemptManager;
  }
  getTabManager() {
    return this.tabManager;
  }
}

class TabManager {
  constructor(private reRender: () => void) {}
  public tabs: {
    [tabIndex: number]: ExamTab;
  } = {};
  public currentTabIndex: number = 0;

  setCurrentTabIndex(tabIndex: number) {
    this.currentTabIndex = tabIndex;
    this.reRender();
  }
  getCurrentTabIndex() {
    return this.currentTabIndex;
  }

  getCurrentTab() {
    return this.tabs[this.currentTabIndex];
  }
  setTab(tabIndex: number, examTab: ExamTab) {
    this.tabs[tabIndex] = examTab;
  }
}

class AttemptManager {
  constructor(private reRender: () => void) {}
  private attempts: ExamAttempt = {};
  setAttempt(questionId: number, attempt: string) {
    this.attempts[questionId] = {
      attempt: attempt,
    };
    this.reRender();
  }
  getAttempt(questionId: number) {
    return this.attempts[questionId]?.attempt;
  }

  resetAttempts() {
    this.attempts = {};
  }
}

export default ExamUtil;
