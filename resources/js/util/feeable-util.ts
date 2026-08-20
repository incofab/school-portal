import { Classification, ClassificationGroup, Student } from '@/types/models';
import { Feeable, FeeCategoryType } from '@/types/types';

export class FeeableUtil {
  private name: string | null;
  constructor(
    private feeable: Feeable | null | undefined,
    private feeableType?: string
  ) {
    if (feeable === null || feeable === undefined) {
      this.name = null;
    } else if (feeableType === FeeCategoryType.Student) {
      const student = feeable as Student;
      this.name = student.user?.full_name ?? student.full_code ?? null;
    } else if ('title' in feeable) {
      this.name = (feeable as Classification | ClassificationGroup).title;
    } else if ('code' in feeable) {
      this.name = 'All Students';
    } else {
      this.name = 'All Students';
    }
  }
  getName() {
    return this.name ?? '';
  }
}

export default function feeableUtil(
  feeable: Feeable | null | undefined,
  feeableType?: string
) {
  return new FeeableUtil(feeable, feeableType);
}
