pragma solidity ^0.4.11;
    
contract Multiparty {

   //Variables
   
   enum State {cancelled, finished}
   address ttp;
   struct Receiver{
      address receiver;
      string hB;
      string keyB;
      State state;
      bool isValue;
   }
   struct Message{
      uint id;
      address sender;
      mapping(address => Receiver) receivers; //All receivers
      bool isValue;
   }
   mapping(address => mapping(uint => Message)) messages;   
   
   //Constructor
   
   constructor(){
      ttp = msg.sender;
   }
   //Events
   
   event cancelEvent(
      address cancelledUser,
      string cancelResponse
      );
      
   event finishEvent(
      string resolveResponse
   );
   
   //Main functions    
   
   function cancel (uint id,address[] cancelledReceivers) {

      if (messages[msg.sender][id].isValue){
      
         for (uint i = 0; i<cancelledReceivers.length;i++){
         
            address receiverToCancel = cancelledReceivers[i];
            
            if ((messages[msg.sender][id].receivers[receiverToCancel].
               isValue)&&(messages[msg.sender][id].receivers
               [receiverToCancel].state==State.finished)){
            
               cancelEvent(receiverToCancel,messages[msg.sender]
               [id].receivers[receiverToCancel].hB);
                        
            } else if (!messages[msg.sender][id].receivers
                      [receiverToCancel].isValue){
                      
                addReceiver(id,msg.sender,receiverToCancel,
                            "","",State.cancelled);
                cancelEvent(receiverToCancel,stateToString(messages
                           [msg.sender][id].receivers[receiverToCancel].
                           state));

            } else {
               cancelEvent(receiverToCancel,stateToString(messages
               [msg.sender][id].receivers[receiverToCancel].state));
            }
            
         }
                
      } else {
      
         createMessage(id,msg.sender);
                
         for(uint j= 0; j<cancelledReceivers.length;j++){
         
            address receiverToAdd = cancelledReceivers[j];
            
            addReceiver(id,msg.sender,receiverToAdd,"","",
                       State.cancelled);
            
            cancelEvent(receiverToAdd,stateToString(messages[msg.sender]
            [id].receivers[receiverToAdd].state));
         }
      }
   }
   
   
   function finish(uint id,address sender,address receiver,string _hB,
                  string _keyB){
            
      if (msg.sender==ttp){
               
         if (messages[sender][id].isValue){
                    
            if((messages[sender][id].receivers[receiver].isValue)
              &&(messages[sender][id].receivers[receiver].
              state==State.cancelled)){
                        
               finishEvent(stateToString(messages[sender][id].
                          receivers[receiver].state));
                        
            }else if(!messages[sender][id].receivers[receiver].isValue){
               
               addReceiver(id,sender,receiver,_hB,_keyB,State.finished);
               
               finishEvent(stateToString(messages[sender][id].
                           receivers[receiver].state));
            }else{
               finishEvent(stateToString(messages[sender]
                          [id].receivers[receiver].state));
            }
                    
         }else{
         
            createMessage(id,sender);
                
            addReceiver(id,sender,receiver,_hB,_keyB,State.finished);
                    
            finishEvent(stateToString(messages[sender][id].
                       receivers[receiver].state));
         }
      }
   }
   
   function createMessage(uint id, address sender) private {     
      messages[sender][id].id=id;
      messages[sender][id].sender=sender;
      messages[sender][id].isValue=true;
   }
   
   function addReceiver(uint id, address sender, address receiver,
                  string _hB, string _keyB, State _state) private{
               
      messages[sender][id].receivers[receiver].receiver=receiver;
      messages[sender][id].receivers[receiver].hB=_hB;
      messages[sender][id].receivers[receiver].keyB=_keyB;
      messages[sender][id].receivers[receiver].state=_state;
      messages[sender][id].receivers[receiver].isValue=true;
   }
    
   function stateToString(State state) view private returns 
                 (string){
                 
      if (state==State.cancelled) return "Cancelled";
      if (state==State.finished) return "Finished";
   }
}