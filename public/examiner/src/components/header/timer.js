import K from '../../config/k';
import examHandler from '../../helpers/ExamHandler'
import React, { Component } from 'react'

class Timer extends Component {

    constructor() {
      super();
      this.state = { timerString: '00:00:00' };
      this.timer = 0;

        this.updateTimer = this.updateTimer.bind(this);
        
        examHandler.timerHandler.setUpdateCallback(this.updateTimer);
    }
  
    updateTimer(timeRemaing){
        // console.log('Update timer called '+timeRemaing);
        this.setState({timerString: K.formatTime(timeRemaing)});
    }

    render() {
      return(
          <>
            <span id="timer" className="ml-3">{this.state.timerString}</span>
            {/* <i className="fa fa-pause pointer ml-3 d-none" 
                id="pause-exam" data-toggle="tooltip" data-placement="left" 
                title="Pause this exam. You can resume later" ></i> */}
          </>
      );
    }
  }

  export default Timer