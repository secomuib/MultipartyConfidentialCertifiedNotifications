pragma solidity ^0.4.11;

contract Multipart2 {
   
    //En este caso no es necesario el estado created, ya que solo se usará el contrato en caso de cancelación o finalización.
    enum State {cancelled, finished  }
    address ttp;
    
    //Struct que nos identifica a los receptores de un mensaje
    struct Receiver{
        address receiver;
        string hB;
        string keyB;
        State state;
        bool isValue;
    }
    
    //Struct que contiene todos los parámetros de un mensaje
    struct Message{
        //El identificador corresponde con el ID del mensaje en la BD del servidor.
        uint id;
        address sender;
       
        //Mapping con el conjunto de receptores
        mapping(address => Receiver) receivers;

        //Lista de los receptores que forman las claves del mapping
        address[] addressList;
        
        bool isValue;
    }
   
   //Creamos un mapping de mensajes y un array que contenga las claves del mapping.
    mapping(uint => Message) messages;
    uint [] messagesKeys;
   
   
    constructor(){
        ttp = msg.sender;
    }
        
    event cancelEvent(
        string cancelResponse
        );
        
    event finishEvent(
        string resolveResponse
        );      
        
        
    
    //Función para cancelar el intercambio
    function cancel(uint id,address[] cancelledReceivers) {

        //Comprobamos si el mensaje existe
         if(messages[id].isValue){
             
            for(uint i = 0; i<cancelledReceivers.length;i++){
                
                address receiverToCancel = cancelledReceivers[i];
                
                //Del mismo modo comprobamos si alguno de los receptores ya ha intentado realizar la finalización
                if((messages[id].receivers[receiverToCancel].isValue)&&(messages[id].receivers[receiverToCancel].state==State.finished)){
                    
                    //En caso afirmativo obtenemos el parámetro hB del receptor que ya ha finalizado:
                    cancelEvent(messages[id].receivers[receiverToCancel].hB);
                    
                }else if(!messages[id].receivers[receiverToCancel].isValue){
                    //En el caso que no lo haya finalizado aún debemos introducir el receptor en el struct
                    //indicando el estado cancelado:
                    
                    messages[id].receivers[receiverToCancel].receiver=receiverToCancel;
                    messages[id].receivers[receiverToCancel].hB="";
                    messages[id].receivers[receiverToCancel].keyB="";
                    messages[id].receivers[receiverToCancel].state=State.cancelled;
                    messages[id].receivers[receiverToCancel].isValue=true;
                    
                    //Finalmente introducimos la clave del mapping en el array de claves:
                    messages[id].addressList.push(receiverToCancel);
                }else{
                    //En caso de que se encuentre ya cancelado avisamos de su estado
                    cancelEvent(stateToString(messages[id].receivers[receiverToCancel].state));
                }
             
            }
            
         }else{
             
            //Si el mensaje no existe lo creamos
            messages[id].id=id;
            messages[id].sender=msg.sender;
            messages[id].isValue=true;
            
            //A continuación, introducimos a todos los receptores cuyo interacambio estamos cancelando
            for(uint j= 0; j<cancelledReceivers.length;j++){
                address receiverToAdd = cancelledReceivers[j];
                
                //Introducimos los parámetros específicos de cada uno de ellos
                messages[id].receivers[receiverToAdd].receiver=receiverToAdd;
                messages[id].receivers[receiverToAdd].hB="";
                messages[id].receivers[receiverToAdd].keyB="";
                messages[id].receivers[receiverToAdd].state=State.cancelled;
                messages[id].receivers[receiverToAdd].isValue=true;
                
                //Posteriormente introducimos la clave del mapping en la lista de direcciones:
                messages[id].addressList.push(receiverToAdd);
            }
            
            //Para acabar introducimos la clave del mapping en la lista de mensajes:
            messagesKeys.push(id);
         }
    }
    
    function finish(uint id,address sender,address receiver,string _hB, string _keyB){
        
        //Comprobamos que se trate de la TTP
        if(msg.sender==ttp){
           
            //Comprobamos si ya existe ese mensaje en el contrato
            if(messages[id].isValue){
                
                if((messages[id].receivers[receiver].isValue)&&(messages[id].receivers[receiver].state==State.cancelled)){
                    //En el caso de que el receptor exista y el mensaje se encuentre cancelado, llamamos
                    //al evento de finalización indicandole el estado en el que se encuentra el intercambio:
                    
                    finishEvent(stateToString(messages[id].receivers[receiver].state));
                    
                }else if(!messages[id].receivers[receiver].isValue){
                    //Si el receptor no existe lo introducimos con los parametros adecuados:
                    
                    messages[id].receivers[receiver].receiver=receiver;
                    messages[id].receivers[receiver].hB=_hB;
                    messages[id].receivers[receiver].keyB=_keyB;
                    messages[id].receivers[receiver].state=State.finished;
                    messages[id].receivers[receiver].isValue=true;
                    
                    //Finalmente introducimos la clave del mapping en el array de claves:
                    messages[id].addressList.push(receiver);
                } else{
                    //Si ya se encuentra finalizado avisamos de su estado
                    finishEvent(stateToString(messages[id].receivers[receiver].state));
                }
                
            }else{
                //En caso de no existir creamos el mensaje
                messages[id].id=id;
                messages[id].sender=sender;
                messages[id].isValue=true;
            
                //A continuación creamos introducimos al receptor cuyo interacambio estamos finalizando
                messages[id].receivers[receiver].receiver=receiver;
                messages[id].receivers[receiver].hB=_hB;
                messages[id].receivers[receiver].keyB=_keyB;
                messages[id].receivers[receiver].state=State.finished;
                messages[id].receivers[receiver].isValue=true;
            
                //Posteriormente introducimos la clave del mapping en la lista de direcciones:
                messages[id].addressList.push(receiver);
            
                //Para acabar introducimos la clave del mapping en la lista de mensajes:
                messagesKeys.push(id);
            }
        }
    }
    
    
        
    function getAddress(uint id,address receiver) view public returns (address){
        return messages[id].receivers[receiver].receiver;
    }
    
    
    function gethB(uint id,address receiver) view public returns (string){
        return messages[id].receivers[receiver].hB;
    }
    
    function getState(uint id, address receiver) view public returns (State){
        return messages[id].receivers[receiver].state;
    }


    function stateToString(State state) view private returns (string){
        if (state==State.cancelled) return "Cancelled";
        if (state==State.finished) return "Finished";
    }
}