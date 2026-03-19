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
  const cutoff = 4;

  useEffect(() => {
    if (!enabled) return;

    const handleViolation = () => {
      violationCount.current += 1;

      if (violationCount.current === 1) {
        onWarning(violationCount.current);
      } else if (violationCount.current >= cutoff) {
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
