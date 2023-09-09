import { CourseSession } from '@/types/models';

class QuestionImageHandler {
  private readonly IMG_BASE_URL = import.meta.env.VITE_CONTENT_IMAGE_BASE_URL;

  constructor(private courseable: CourseSession) {}

  getQuestionBaseUrl() {
    return (
      this.IMG_BASE_URL + `/${this.courseable.course_id}/${this.courseable.id}/`
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
    const lastPart = urlPath.split('/').pop();
    if (this.isValidImage(lastPart)) {
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
    if (!['.jpg', '.gif', '.png', 'jpeg'].includes(filename.substr(-4))) {
      return false;
    }
    return true;
  }
}

export default QuestionImageHandler;
