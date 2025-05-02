import {
  Classification,
  ClassificationGroup,
  Institution,
} from '@/types/models';

export class FeeableUtil {
  private name: string | null;
  constructor(
    private feeable:
      | Classification
      | ClassificationGroup
      | Institution
      | null
      | undefined
  ) {
    if (feeable == null || feeable == undefined) {
      this.name = null;
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
  feeable: Classification | ClassificationGroup | Institution | null | undefined
) {
  return new FeeableUtil(feeable);
}
