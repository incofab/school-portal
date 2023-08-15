import Data from '../../../config/startup';
import {connect} from 'react-redux'
import K from '../../../config/k'

var gProps;

const tabSelected = (e) => {
    var tab_index = e.target.attributes.getNamedItem('data-tab_index').value;
    
    gProps.dispatch({
        type: K.ACTION_TAB_CHANGED,
        payload: {
            selected_tab: tab_index
        }
    });
}

const examTab = (props) => {
    gProps = props;
    
    var tabs = Data.exam_data.all_exam_subject_data.map((subject, index) => (
        
        <li className="nav-item" key={'exam-tab-'+index} >
            <div className={'nav-link text-primary cursor-pointer '
                +((props.currentTab==index)?'active':'')} 
                data-tab_index={index}
                data-toggle="tab" id={'#nav-'+subject.exam_subject_id} role="tab"
                onClick={tabSelected}
            >
                {subject.}
            </div>
        </li>
    ));

    return (
        <nav id="exam-tabs" className="clearfix">
            <ul className="nav nav-tabs float-left" id="nav-tab" role="tablist" >
                {tabs}
            </ul>
            <div className="float-right py-2">
                <b >Exam No: <span id="exam-no">{Data.data.exam_no}</span></b>
            </div>
        </nav>
    );
}

const mapStateToProps = (state) => ({
    currentTab: state.current_tab
});

export default connect(mapStateToProps)(examTab);



