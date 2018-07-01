pragma solidity ^0.4.11;

contract CertifiedMail {
   
   //Parties involved 
    address sender;
    address receiver;
    address ttp;

    string hB; //NRR proof
    string keyB; //Symmetric key encrypted with pubKey from B
    
    //Possible states
    enum State { created, cancelled, finished }
    State public state;
    bytes15 stateString;
    
    function CertifiedMail (address _sender, address _receiver){
        ttp = msg.sender;
        sender = _sender;
        receiver = _receiver;
        state = State.created;
        }


    event cancelEvent(
        string cancelResponse
        );
        
    event finishEvent(
        string resolveResponse
        );
        

    function cancel() {
         if(msg.sender==sender){
             if(state==State.created){
                 state=State.cancelled;
                 cancelEvent(getState());
             }else if (state == State.finished){
                 cancelEvent(hB);
             }
         }
    }
    
    function finish(string _hB, string _keyB){
        if (msg.sender==ttp){
            if(state==State.cancelled){
                finishEvent(getState());
            }else{
                hB=_hB;
                keyB=_keyB;
                state=State.finished;
                finishEvent(getState());
            }
        }
    }
    
    function getState() view public returns (string){
        if (state==State.cancelled) return "Cancelled";
        if (state==State.created) return "Created";
        if (state==State.finished) return "Finished";
    }
}