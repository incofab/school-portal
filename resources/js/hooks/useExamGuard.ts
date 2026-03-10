import { useEffect, useRef } from 'react';

interface UseExamGuardProps {
  onWarning: (count: number) => void;
  onTerminate: () => void;
  enabled: boolean;
}

export const useExamGuard = ({
  onWarning,
  onTerminate,
  enabled,
}: UseExamGuardProps) => {
  const violationCount = useRef(0);

  useEffect(() => {
    if (!enabled) return;

    const handleViolation = () => {
      violationCount.current += 1;

      if (violationCount.current === 1) {
        onWarning(violationCount.current);
      } else if (violationCount.current >= 2) {
        onTerminate();
      }
    };

    const handleVisibilityChange = () => {
      if (document.visibilityState === 'hidden') {
        handleViolation();
      }
    };

    const handleBlur = () => {
      handleViolation();
    };

    // Listen for tab switching and window blurring
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('blur', handleBlur);

    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      window.removeEventListener('blur', handleBlur);
    };
  }, [enabled, onWarning, onTerminate]);

  return { count: violationCount.current };
};

/*
import React, { useState } from 'react';
import { useExamGuard } from './hooks/useExamGuard';

const ExamPage: React.FC = () => {
  const [isExamActive, setIsExamActive] = useState(true);
  const [showWarning, setShowWarning] = useState(false);

  const handleTerminate = () => {
    setIsExamActive(false);
    alert("Exam terminated due to multiple tab switches.");
    // Call your API here to submit the exam automatically
    // submitExam();
  };

  const handleWarning = (count: number) => {
    setShowWarning(true);
  };

  useExamGuard({
    enabled: isExamActive,
    onWarning: handleWarning,
    onTerminate: handleTerminate,
  });

  return (
    <div className="exam-container">
      <h1>EduManager Examination</h1>
      
      {showWarning && (
        <div style={{ color: 'red', border: '1px solid red', padding: '10px' }}>
          <strong>Warning!</strong> You left the exam screen. 
          The next time you do this, your exam will be submitted automatically.
          <button onClick={() => setShowWarning(false)}>I Understand</button>
        </div>
      )}

      {isExamActive ? (
        <div>
          // Your Exam Questions Here *
          <p>Please stay on this tab until you finish.</p>
        </div>
      ) : (
        <h2>Exam Closed</h2>
      )}
    </div>
  );
};

export default ExamPage;

*/
