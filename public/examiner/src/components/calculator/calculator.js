import './calculator.css'
import {connect} from 'react-redux'
import K from '../../config/k'
import React, { Component } from 'react'

export class calculator extends Component {
    
    constructor(props){
        super(props);
        this.state = {display: ''}
        this.input = this.input.bind(this);
        this.operator = this.operator.bind(this);
        this.equal = this.equal.bind(this);
        this.closeCalculator = this.closeCalculator.bind(this);
    }

    closeCalculator() {
        this.props.dispatch({
            type: K.ACTION_TOGGLE_CALCULATOR,
            payload: {
                show_calculator: false
            }
        })
    }

    input(e){
        var input = e.target.attributes.getNamedItem('data-input').value;        
        
        this.setState({
            display: this.state.display + input,
        })
    }
    
    equal(){
        try{ 
            var result = eval(this.state.display);
            
            this.setState({
                display: result,
            })
        }catch(err) {
            this.setState({
                display: 'Error',
            })
        } 
    }
    
    operator(e){
        var input = e.target.attributes.getNamedItem('data-input').value;
        
        if(input === "=")
        {
            this.equal();

            return;
        }

        this.setState({
            display: '',
        })
    }

    render() {
        return (
        <div>
            <div id="examdriller-calculator" 
                className={'s '+(this.props.show_calculator ? 'show' : '')}>
                <div className="clearfix">
                    <button className="btn btn-secondary btn-sm toggleCalculator pointer mb-1 mr-1 w-100" 
                    onClick={this.closeCalculator}
                    id="close-calculator">&times; close</button>
                </div>
                <div className="box" >
                    <div className="display">
                        <input type="text" readOnly size="18" id="d"
                            value={this.state.display}/>
                    </div>
                    <div className="keys">
                        <p>
                            <input type="button" className="button gray" value="mrc" 
                                data-input="" onClick={this.operator}/>
                            <input type="button" className="button gray" value="m-" 
                                data-input="" onClick={this.operator}/>
                            <input type="button" className="button gray" value="m+" 
                                data-input="" onClick={this.operator}/>
                            <input type="button" className="button pink" value="/" 
                                data-input="/" onClick={this.input}/>
                        </p>
                        <p>
                            <input type="button" className="button black" value="7"
                                data-input="7" onClick={this.input}/>
                            <input type="button" className="button black" value="8" 
                                data-input="8" onClick={this.input}/>
                            <input type="button" className="button black" value="9" 
                                data-input="9" onClick={this.input}/>
                            <input type="button" className="button pink" value="&times;" 
                                data-input="*" onClick={this.input}/>
                        </p>
                        <p>
                            <input type="button" className="button black" value="4" 
                                data-input="4" onClick={this.input}/>
                            <input type="button" className="button black" value="5" 
                                data-input="5" onClick={this.input}/>
                            <input type="button" className="button black" value="6" 
                                data-input="6" onClick={this.input}/>
                            <input type="button" className="button pink" value="-" 
                                data-input="-" onClick={this.input}/>
                        </p>
                        <p>
                            <input type="button" className="button black" value="1" 
                                data-input="1" onClick={this.input}/>
                            <input type="button" className="button black" value="2" 
                                data-input="2" onClick={this.input}/>
                            <input type="button" className="button black" value="3" 
                                data-input="3" onClick={this.input}/>
                            <input type="button" className="button pink" value="+" 
                                data-input="+" onClick={this.input}/>
                        </p>
                        <p>
                            <input type="button" className="button black" value="0" 
                                data-input="0" onClick={this.input}/>
                            <input type="button" className="button black" value="." 
                                data-input="." onClick={this.input}/>
                            <input type="button" className="button black" value="C" 
                                data-input="" onClick={this.operator}/>
                            <input type="button" className="button orange" value="=" 
                                data-input="=" onClick={this.operator}/>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        )
    }
}

const mapStateToProps = (state) => {
    return {
        show_calculator: state.show_calculator,
    }
}
export default connect(mapStateToProps)(calculator);

