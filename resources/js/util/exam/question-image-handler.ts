import { CourseSession } from '@/types/models';

class QuestionImageHandler {
  private readonly IMG_BASE_URL = import.meta.env.VITE_CONTENT_IMAGE_BASE_URL;

  constructor(private courseable: CourseSession) {}

  getQuestionBaseUrl() {
    return (
      this.IMG_BASE_URL +
      `/institutions/${this.courseable.institution_id}/ccd/${this.courseable.course_id}/${this.courseable.id}/`
    );
  }

  handleImages(htmlStr: string) {
    if (!htmlStr) {
      return '';
    }
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlStr, 'text/html');
    const imgTags = doc.querySelectorAll('img');

    imgTags.forEach((img) => {
      const src = img.getAttribute('src') ?? '';
      const alt = img.getAttribute('alt') ?? '';
      const url = this.getImageUrl(src, alt);
      img.setAttribute('src', url);
    });

    return doc.documentElement.innerHTML;
  }

  getImageUrl(src: string, alt: string) {
    let filename = this.getUrlLastPath(src) ?? '';
    if (!this.isValidImage(filename)) {
      filename = alt;
    }
    return this.getQuestionBaseUrl() + filename;
  }

  getUrlLastPath(urlPath: string): string {
    let lastPart = urlPath.split('/').pop();
    const base64DataIndex = urlPath.indexOf('data:');
    if (base64DataIndex > -1) {
      // Handle base64 images here
      lastPart = urlPath.substring(base64DataIndex);
      let amperSandIndex = lastPart.indexOf('&');
      return lastPart.substring(
        0,
        amperSandIndex == -1 ? undefined : amperSandIndex
      );
    }

    if (!lastPart || lastPart.length < 5 || this.isValidImage(lastPart)) {
      return lastPart ?? '';
    }

    const prefix = 'filename=';
    const startPoint = urlPath.substring(urlPath.lastIndexOf(prefix));
    // console.log(filename, ' | | ', startPoint.substring(prefix.length, startPoint.indexOf("&")));
    const amperSandIndex = startPoint.indexOf('&');
    return this.getUrlLastPath(
      startPoint.substring(
        prefix.length,
        amperSandIndex == -1 ? undefined : amperSandIndex
      )
    );
  }

  isValidImage(filename: string | undefined) {
    if (!filename || filename.length < 4) {
      return false;
    }
    if (filename.startsWith('data:')) {
      return true;
    }
    if (
      !['.jpg', '.gif', '.png', 'jpeg'].includes(
        filename.substring(filename.length - 4)
      )
    ) {
      return false;
    }
    return true;
  }
}

export default QuestionImageHandler;
