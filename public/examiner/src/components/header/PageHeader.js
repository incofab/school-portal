import Timer from './timer'
import K from '../../config/k'
import examSync from '../../helpers/ExamSync'
import './header.css'
import {connect} from 'react-redux'
import Data from '../../config/startup';

const HeaderRender = (props) => {

    function toggleCalculator() {
        props.dispatch({
            type: K.ACTION_TOGGLE_CALCULATOR,
            payload: {
                show_calculator: !props.show_calculator,
            }
        })
    }

    function pauseExam(){
        
        if(!window.confirm('Do you want to submit this exam? You can still resume later')) return;

        examSync.pauseExam();
    }

    return (
    <header className="app-header py-2">
        <div className="row w-100">
            <div className="col-7 text-truncate text-left">
                <div className="pl-1" id="name">{Data.data.firstname} {Data.data.lastname}</div>
            </div>
            <div className="col-5" >
                <div align="right" className="pr-3 clearfix">
                    <div className="float-right text-truncate">
                        <i className="fa fa-calculator toggleCalculator pointer"
                            onClick={toggleCalculator} ></i>
                        <Timer />
                        <i className={`fa fa-pause pointer ml-3 d-none`} onClick={pauseExam} 
                            id="pause-exam" data-toggle="tooltip" data-placement="left" 
                            title="Pause/Play this exam." ></i>
                        {/*
                        <span id="timer" className="ml-3">00:00:00</span>
                        <i className="fa fa-pause pointer ml-3 d-none" 
                            id="pause-exam" data-toggle="tooltip" data-placement="left" 
                            title="Pause this exam. You can resume later" ></i>
                        */}
                    </div>
                </div>
            </div>
        </div>
    </header>
    )
}

// export default HeaderRender;
const mapStateToProps = (state) => {
    return {
        show_calculator: state.show_calculator,
    }
}
export default connect(mapStateToProps)(HeaderRender);

