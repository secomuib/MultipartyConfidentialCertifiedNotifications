pragma solidity ^0.4.11;

contract IDCertifiedMail {
   
   enum State {cancelled, finished }   //No need for created state.
   address ttp;
   struct Message{
      address sender;
      address receiver;
      string hB;
      string keyB;
      State state;
      bool isValue;
   }
   
   //Hash table with all messages
   mapping(address => mapping(uint => Message)) messages;

   
   constructor(){
      ttp = msg.sender;
   }
        
   event cancelEvent(
      string cancelResponse
      );
        
   event finishEvent(
      string resolveResponse
      );      
        
   //Main cancel protocol     
   function cancel(uint id,address receiver) {
   
      if(messages[msg.sender][id].isValue){
         if(messages[msg.sender][id].state==State.cancelled){
            cancelEvent(stateToString(messages[msg.sender][id].state));
         }else{
            cancelEvent(messages[msg.sender][id].hB);
         }
      }else{
        createMessage(id,msg.sender,receiver, "",
                       "",State.cancelled);
      }
   }
        
   //Main finish protocol     
   function finish(uint id,address sender,address receiver,
                    string _hB, string _keyB){
                    
      if(msg.sender==ttp){
         if(messages[sender][id].isValue){
               finishEvent(stateToString(messages[sender][id].state));
         }else{
            createMessage(id,sender,receiver, _hB,
                          _keyB,State.finished);
         }
      }
   }    
    
    //Private function to create message
    function createMessage(uint id, address sender, address receiver, 
                       string _hB,string _keyB,State state) private {
                          
            messages[sender][id].sender=sender;
            messages[sender][id].receiver=receiver;
            messages[sender][id].hB=_hB;
            messages[sender][id].keyB=_keyB;
            messages[sender][id].state=state;         
            messages[sender][id].isValue=true;
    }
    
    
    function stateToString(State state) view private returns (string){
        if (state==State.cancelled) return "Cancelled";
        if (state==State.finished) return "Finished";
    }
}
