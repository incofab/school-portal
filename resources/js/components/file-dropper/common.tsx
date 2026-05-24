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
    extensionLabels: ['.jpg', '.jpeg', '.png', '.webp'],
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
  Doc: {
    extensionLabels: ['.doc', '.docx'],
    mimes: {
      'application/msword': [],
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        [],
    },
  },
  Zip: {
    extensionLabels: ['.zip'],
    mimes: {
      'application/zip': [],
    },
  },
  Media: {
    extensionLabels: [
      '.jpg',
      '.jpeg',
      '.png',
      '.webp',
      '.pdf',
      '.doc',
      '.docx',
      '.mp4',
      '.mov',
      '.avi',
      '.mkv',
      '.mp3',
      '.wav',
    ],
    mimes: {
      'image/jpeg': [],
      'image/png': [],
      'image/webp': [],
      'application/pdf': [],
      'application/msword': [],
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        [],
      'video/mp4': [],
      'video/quicktime': [],
      'video/x-msvideo': [],
      'video/x-matroska': [],
      'audio/mpeg': [],
      'audio/wav': [],
    },
  },
  Excel: {
    extensionLabels: ['.xlsx'],
    mimes: {
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': [],
    },
  },
};
