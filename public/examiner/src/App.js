import React, { Component } from 'react'
import {Provider} from 'react-redux'
import PageHeader from './components/header/PageHeader';
import ExamTab from './components/body/exam_tab/examTab';
import QuestionComponent from './components/body/question/question';
import FooterNav from './components/footer/footer';
import Store from './redux/store';
import Data from './config/startup';
import examHandler from './helpers/ExamHandler'
import Calculator from './components/calculator/calculator'
import './App.css';
import examSync from './helpers/ExamSync';
import examActions from './helpers/ExamActions';
// import logo from './logo.svg';

let durationInSec = Data.exam_data.meta.time_remaining;

examHandler.timerHandler.start(durationInSec, () =>{
  // console.log('Time Elapsed, Exam Ended');
  examSync.submit();
});

class App extends Component{

  
  componentDidMount(){
    document.addEventListener("keyup", this.keyListener, false);
  }

  componentWillUnmount(){
    document.removeEventListener("keyup", this.keyListener, false);
  }
  
  keyListener(e) {
    console.log("Keylistener", e, e.key);
    // if(!e.key) return;
    // this.handleKeyPresses(Store.getState(), e.key);
    let keyUpperCase = e.key;
    // let props = Store;
    
    switch (keyUpperCase) {
      case 'A':case 'a':
        examActions.answerSelected(Store, 'A');
        break;
      case 'B':case 'b':
        examActions.answerSelected(Store, 'B');
        break;
      case 'C':case 'c':
        examActions.answerSelected(Store, 'C');
        break;
      case 'D':case 'd':
        examActions.answerSelected(Store, 'D');
        break;
      case 'P':case 'p':
        examActions.gotoPreviousQuestion(Store);
        break;
      case 'N':case 'n':
        examActions.gotoNextQuestion(Store);
        break;
      case 'S':case 's':
        if(!window.confirm('Do you want to submit and end this exam?')) return;
        examSync.submit();
        break;
      case 'R':case 'r':
        console.log('R clicked');
        break;
    
      default:
        break;
    }
  }

  render() {
    // console.log('Store.current_tab = ',Store.getState());
    return (
      <Provider store={Store}>
        <PageHeader/>
        <div className="container-fluid" style={{marginTop: '85px'}} 
          id="exam-base-container" >
          <div id="exam-layout" >
            <ExamTab />
            <QuestionComponent />
            <FooterNav />
          </div>
          <Calculator />
        </div>
      </Provider>
    );
  }
  
}
export default App;

