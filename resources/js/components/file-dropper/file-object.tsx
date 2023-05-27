import { Nullable } from '@/types/types';

export default class FileObject {
  public file: File;
  public name: string;
  public extension: Nullable<string>;

  constructor(file: File) {
    this.file = file;
    this.extension = FileObject.getExtension(file.name);
    this.name = FileObject.getFilenameWithoutExtension(file.name);
    this.name = FileObject.cleanFilename(this.name);
  }

  public getNameWithExtension(): string {
    if (!this.extension) {
      return this.name;
    }
    return `${this.name}.${this.extension}`;
  }

  public static getFilenameWithoutExtension(filename: string) {
    const parts = filename.split('.');
    if (parts.length === 1) {
      return parts[0];
    }
    return parts.slice(0, parts.length - 1).join('.');
  }

  public static getExtension(filename: string): Nullable<string> {
    const parts = filename.split('.');
    if (parts.length === 1) {
      return null;
    }
    return parts[parts.length - 1];
  }

  public static cleanFilename(filename: string): string {
    return filename.replaceAll(/\s/g, '_').replaceAll(/[^\w\d-_]/g, '');
  }
}
