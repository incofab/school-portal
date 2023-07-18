// 4mb
export const MAX_FILE_SIZE_BYTES = 1096 * 1000;

export function bytesToMb(size: number) {
  return size / 1_000_000;
}

export interface FileAcceptObject {
  extensionLabels: string[];
  mimes: {
    [mime: string]: string[];
  };
}

export const FileDropperType = {
  Image: {
    extensionLabels: ['.jpg', '.jpeg', '.png'],
    mimes: {
      'image/jpeg': [],
      'image/png': [],
    },
  },
  Pdf: {
    extensionLabels: ['.pdf'],
    mimes: {
      'application/pdf': [],
    },
  },
  Zip: {
    extensionLabels: ['.zip'],
    mimes: {
      'application/zip': [],
    },
  },
  Excel: {
    extensionLabels: ['.xlsx'],
    mimes: {
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': [],
    },
  },
};
