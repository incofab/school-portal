export type ResultScoreValue = string | number | null | undefined;

export interface ResultScoreEntry {
  ass?: Record<string, ResultScoreValue>;
  exam?: ResultScoreValue;
}

export function getResultTotalScore(
  result: ResultScoreEntry,
  includeExam: boolean
) {
  let score = 0;
  Object.values(result.ass ?? {}).forEach((value) => {
    score += Number(value);
  });

  const totalScore = score + (includeExam ? Number(result.exam ?? 0) : 0);
  return isNaN(totalScore) ? '' : totalScore;
}

export function validateResultScore(score: number | string, maxScore?: number) {
  const numericScore = Number(score);

  if (isNaN(numericScore)) {
    return {
      ok: false,
      message: 'Score invalid. It must be a number',
    };
  }

  if (!maxScore || maxScore === 0) {
    return { ok: true };
  }

  if (numericScore > maxScore) {
    return {
      ok: false,
      message: `Score cannot be greater than ${maxScore}`,
    };
  }

  return { ok: true };
}

export function removeExamFromResultEntry<T extends ResultScoreEntry>(
  result: T
): Omit<T, 'exam'> {
  const data = { ...result };
  delete data.exam;
  return data;
}

export function hasMeaningfulResultScore(
  result: ResultScoreEntry,
  includeExam: boolean
) {
  const hasAssessmentScore = Object.values(result.ass ?? {}).some(hasValue);
  const hasExamScore = includeExam && hasValue(result.exam);

  return hasAssessmentScore || hasExamScore;
}

export function hasResultScoreChanged(
  current: ResultScoreEntry,
  original: ResultScoreEntry | undefined,
  includeExam: boolean
) {
  if (!original) {
    return hasMeaningfulResultScore(current, includeExam);
  }

  return (
    !areScoreMapsEqual(current.ass ?? {}, original.ass ?? {}) ||
    (includeExam && !areScoreValuesEqual(current.exam, original.exam))
  );
}

function areScoreMapsEqual(
  current: Record<string, ResultScoreValue>,
  original: Record<string, ResultScoreValue>
) {
  const keys = Array.from(
    new Set([...Object.keys(current), ...Object.keys(original)])
  );

  return keys.every((key) => areScoreValuesEqual(current[key], original[key]));
}

function areScoreValuesEqual(
  current: ResultScoreValue,
  original: ResultScoreValue
) {
  if (!hasValue(current) && !hasValue(original)) {
    return true;
  }

  if (isNumericValue(current) && isNumericValue(original)) {
    return Number(current) === Number(original);
  }

  return String(current ?? '') === String(original ?? '');
}

function hasValue(value: ResultScoreValue) {
  return value !== null && value !== undefined && String(value) !== '';
}

function isNumericValue(value: ResultScoreValue) {
  return hasValue(value) && !isNaN(Number(value));
}
