// Ambient typing for the runtime object that browser-runtime.js attaches to
// `window` inside the tutorial browser. Kept separate from app types since
// this never runs in the real application.
export interface TutorialRuntimeApi {
  showCard(eyebrow: string, title: string, description: string): void;
  hideCard(): void;
  setHighlight(rect: {
    x: number;
    y: number;
    width: number;
    height: number;
  }): void;
  clearHighlight(): void;
  moveCursor(x: number, y: number): void;
  hideCursor(): void;
  clickPulse(x: number, y: number): void;
  zoomToPoint(x: number, y: number, scale?: number): void;
  resetZoom(): void;
}

declare global {
  interface Window {
    __tutorialRuntime?: TutorialRuntimeApi;
  }
}

export {};
