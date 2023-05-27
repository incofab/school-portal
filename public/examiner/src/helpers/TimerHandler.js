import examSync from '../helpers/ExamSync'

class TimerHandler{

    intervalId;
    duration;
    timeElapsed;
    timeRemaining;

    timeElapsedCallbackFn;
    updateCallbackFn;

    constructor(){
        this.reset();
    }

    pingInterval = 15;
    pingCounter = 0;

    setUpdateCallback(updateCallbackFn) {this.updateCallbackFn = updateCallbackFn;}

    start(duration, callback){ 

        this.timeElapsedCallbackFn = callback;
        
        this.duration = duration;
        
        this.evaluateTime();
        
        this.intervalId = setInterval(() => {

            this.timeElapsed++;
            this.pingCounter++;
            
            this.evaluateTime();
            
        }, 1000); 
    }
    
    evaluateTime(){
        // console.log(
        //     'Duration = '+this.duration,
        //     'Time remaining = '+this.timeRemaining,
        //     'Time Elapsed = '+this.timeElapsed,
        // "Timer: "+this.timeRemaining);
        
        if(this.pingCounter > 15){
            this.pingCounter = 0;
            examSync.uploadNow();
        }

        
        this.timeRemaining = this.duration - this.timeElapsed;
        
        if(this.timeRemaining < 1){
            this.timeElapsedCallbackFn();
            
            this.stop();
        }
        
        // if(!this.lessThan5MinsCalled && this.timeRemaining < (5 * 60)){
        //     this.lessThan5MinsCalled = true;
        //     // Time less than 5 minutes, turn timer color red
        //     this.examTimerHooks.timeLessThan5Mins();
        // }
        
        // if(!this.lessThan1MinCalled && this.timeRemaining < (1 * 60)){
        //     this.lessThan1MinCalled = true;
        //     // Time less than 1 minute, start blinking
        //     this.examTimerHooks.timeLessThan1Min();
        // }
        // this.examTimerHooks.updateTime(K.formatTime(this.timeRemaining));
        
        if(this.updateCallbackFn){
            this.updateCallbackFn(this.timeRemaining);
        }
    }


    stop(){
        if(this.intervalId !== 0) {
            clearInterval(this.intervalId);
        }
        this.reset();
    }
    
    reset(){
        this.timeElapsed = 0;
        this.duration = 0;
        this.intervalId = 0;
        this.timeRemaining = 0;
        this.timeElapsedCallbackFn = null;
    }

    
    resume(){
        this.start(this.duration, this.timeElapsedCallbackFn);
    }
    
    pause(){
        if(this.intervalId !== 0)  clearInterval(this.intervalId);
    }

}

export default TimerHandler;