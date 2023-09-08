class ExamTimer {
  private intervalId: number = 0;
  private duration: number = 0;
  private timeElapsed: number = 0;
  private timeRemaining: number = 0;
  private readonly PING_INTERVAL = 5;
  private pingCounter = 0;

  constructor(
    private onTimerTick: (timeRemaining: number) => void,
    private onTimeElapsed: () => void,
    private onIntervalPing: () => void
  ) {
    this.reset();
  }

  start(duration: number) {
    this.duration = duration;
    this.evaluateTime();

    this.intervalId = setInterval(() => {
      this.timeElapsed++;
      this.pingCounter++;
      this.evaluateTime();
    }, 1000);
  }

  evaluateTime() {
    // console.log(
    //     'Duration = '+this.duration,
    //     'Time remaining = '+this.timeRemaining,
    //     'Time Elapsed = '+this.timeElapsed,
    // "Timer: "+this.timeRemaining);

    if (this.pingCounter > this.PING_INTERVAL) {
      this.pingCounter = 0;
      this.onIntervalPing();
    }

    this.timeRemaining = this.duration - this.timeElapsed;

    if (this.timeRemaining < 1) {
      this.onTimeElapsed();
      this.stop();
    }

    this.onTimerTick(this.timeRemaining);
  }

  stop() {
    if (this.intervalId !== 0) {
      clearInterval(this.intervalId);
    }
    this.reset();
  }

  reset() {
    this.timeElapsed = 0;
    this.duration = 0;
    this.intervalId = 0;
    this.timeRemaining = 0;
  }

  resume() {
    this.start(this.timeRemaining);
  }

  pause() {
    if (this.intervalId !== 0) {
      clearInterval(this.intervalId);
    }
  }
}

export default ExamTimer;
