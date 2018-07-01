<?php
require_once('funciones.php');
session_start();
if(!isset($_SESSION['user_session'])||$_SESSION['ttp']==false){
	header("Location: login.php");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-32">
    <meta charset="utf-8">

	<!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

	<!-- Validation plugin -->
	<script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>

	
	<!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

	<!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

	<!-- stanford cryptography library -->
	 <script src="https://bitwiseshiftleft.github.com/sjcl/sjcl.js"></script>
	 <script type="text/javascript" src="js/sjcl.js"></script>

	 <!-- Web3 -->
	 <script src="./node_modules/web3/dist/web3.min.js"></script>
	 
	 <!-- Script with the ABI var from the contract -->
	 <script type="text/javascript" src="js/abi.js"></script> 

    <link rel="stylesheet" type="text/css" href="css/main.css">

	<script>
		
		var Web3 = require('web3');
        if (typeof web3 !== 'undefined') {
            web3 = new Web3(web3.currentProvider);
        } else {
            web3 = new Web3(new web3.providers.HttpProvider("http://localhost:8545"));
        }
        web3.eth.getAccounts(function (error, accounts) {
            web3.eth.defaultAccount = accounts[0];
            console.log(web3.eth.defaultAccount);
        });

		function finish(contractAddress,mensajeId,KtModificada,hc,hB,destSignKey,destCiphKey,addressRemitente,addresDestinatario,idDest,email){
			//1 - Desciframos la clave simétrica con la clave privada de la TTP
			//1.1 Obtenemos el valor correcto de Kt
			Kt = KtModificada.replace(/¬/g,'"');
			//1.2 Obtenemos la clave privada de la TTP
			var privKey = localStorage.getItem("ttpPrivCiph" + "<?php echo $_SESSION['user_session'];?>");
			privKey = new sjcl.ecc.elGamal.secretKey(
				sjcl.ecc.curves.c256,
				sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.base64.toBits(privKey))
			)

			//Obtenemos la clave privada para firmar:
			var signPrivKey = localStorage.getItem("ttpPrivSign" + "<?php echo $_SESSION['user_session']; ?>");
			signPrivKey = new sjcl.ecc.ecdsa.secretKey(
				sjcl.ecc.curves.c256,
				sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.base64.toBits(signPrivKey))
			);

			//2 - Cargamos el contrato y intentamos finalizar el intercambio 
			if(web3.eth.defaultAccount != 'undefined'){
				//2.1 - En primer lugar cargamos el contrato
				var emailContract  = loadContract(contractAddress);
				//2.2 - En segundo lugar comprobamos que el valor de hB recibido es correcto:
				var destSignKey = new sjcl.ecc.ecdsa.publicKey(sjcl.ecc.curves.c256, sjcl.codec.base64.toBits(destSignKey));
				var confirmacion = destSignKey.verify(sjcl.hash.sha256.hash(hc + Kt), sjcl.codec.base64.toBits(hB));
				if(confirmacion){		//Si la firma es correcta procedemos a finalizar el intercambio
					//2.3 - Desciframos la clave simétrica con la clave privada de la TTP
					var symKey = sjcl.decrypt(privKey,Kt);
					//2.4 - Cifrar la clave simétrica con la clave privada de B y finalizar el contrato.
					var destCiphKey = new sjcl.ecc.elGamal.publicKey(sjcl.ecc.curves.c256, sjcl.codec.base64.toBits(destCiphKey));
					var encSymKey = sjcl.encrypt(destCiphKey, sjcl.codec.base64.fromBits(symKey));
					var finishEvent = emailContract.finishEvent();
					finishEvent.watch(function(error,result){
						if(result){
							if(result.args.resolveResponse == "Cancelled"){
								//2.5a - Si el contrato ya estaba cancelado se lo indicamos al usuario y firmamos la información
								var hashIntervencion = sjcl.hash.sha256.hash(result.args.resolveResponse,hB);
								hashIntervencion = sjcl.codec.base64.fromBits(hashIntervencion);
								var intervencionFirmada = signPrivKey.sign(hashIntervencion);
								intervencionFirmada = sjcl.codec.base64.fromBits(intervencionFirmada);

								var data = {
									"Ka": result.args.resolveResponse,
									"ttp_inter_result": intervencionFirmada,
									"idDest": idDest,
									"mensajeId": mensajeId
								}
								$.ajax({
								data: data,
								url: 'final_process.php',
								type: 'POST',
								beforeSend:function(){
						
								},
								success: function(response){
									console.log(response);
									if(response=="ok"){
										setTimeout('window.location.href="ttp.php?reclamaciones=true"',300);
									}
								}
							});
							}else if(result.args.resolveResponse == "Finished"){
								console.log(result);
								
								//2.5b - Si el contrato no estaba cancelado le proporcionamos la clave simétrica firmada:		
								console.log(email);
								var hashClave = sjcl.hash.sha256.hash(symKey+email);
								var firmaClave = signPrivKey.sign(hashClave);
								firmaClave = sjcl.codec.base64.fromBits(firmaClave);
						        symKey = symKey.concat("FIRMA:" + firmaClave);

								var data = {
									"ttp_inter_result":"",
									"Ka": symKey,
									"idDest": idDest,
									"mensajeId": mensajeId
								}
								$.ajax({
								data: data,
								url: 'final_process.php',
								type: 'POST',
								beforeSend:function(){
							
								},
								success: function(response){
									console.log(response);
									if(response=="ok"){
										setTimeout('window.location.href="ttp.php?reclamaciones=true"',300);
									}
								}
							});
							}
							
						}
					});
					console.log(hB);
					console.log(encSymKey);
					emailContract.finish(mensajeId,addressRemitente,addresDestinatario,hB,encSymKey,(err,res) => {
				
					});
				}
			}
		}	

		//Función para cargar el contrato
		function loadContract(contractAddress) {
			var contract = web3.eth.contract(abi);
			var emailContract = contract.at(contractAddress);
			return emailContract;
		}

		//Función para cerrar sesión
		function logout(){
		setTimeout('window.location.href= "logout.php"',300);
		}
	</script>
</head>
<body>
   <div class="container">
        <div class="row">
            <div class="">
				<button type="button" class="btn btn-default btn-sm botonLogout" onclick="logout()">
				<span class="glyphicon glyphicon-log-out">
				</span> Log out
				</button>
			</div>
        </div>
        <hr />
        <div class="row">
            <div class="col-sm-3 col-md-2">
                <ul class="nav nav-pills nav-stacked">
					<li class="active"><a href="ttp.php?reclamaciones=true">Reclamaciones</a></li>
                </ul>
            </div>
            <div class="col-sm-9 col-md-10">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#home" data-toggle="tab">
                            <span class="glyphicon glyphicon-inbox">
                            </span>Mensajes
                        </a>
                    </li>
                </ul>
                <!-- Tab panes -->
                <div class="tab-content">
                    <div class="tab-pane fade in active" id="home">
                        <div class="list-group">

						<?php
						$conn = establecer_conexion();
						$idTTP = $_SESSION['user_session'];
						$sql = "SELECT distinct mensaje.Id AS mensajeId, mensaje.address_remitente,mensaje.date,mensaje.Kt, mensaje.Hc, usuario.address AS dest_address, 
						usuario.signKey, usuario.ciphKey, usuario.Id AS idUser, ttp.contract, dest_variables.hB, usuario.email FROM mensaje INNER JOIN dest_variables ON dest_variables.Id_mensaje=mensaje.Id 
						INNER JOIN ttp ON ttp.Id=mensaje.Id_ttp INNER JOIN usuario ON usuario.Id=dest_variables.Id_usuario WHERE dest_variables.Ka='pending' 
						AND mensaje.Id_ttp='$idTTP'";
						$run = mysqli_query($conn,$sql);
						while($resultado=mysqli_fetch_assoc($run)){
							$salida[]=$resultado;
						}
						if (isset($salida)){
							
							foreach($salida as $value){
								$KtModificada = str_replace('"','¬',$value['Kt']);
						?>
                            <a href="#" class="list-group-item">
                                <span class=""><b>Sender:</b> <?=$value['address_remitente'];?> </span> <span class=""><b>Receiver:</b> <?= $value['dest_address'];?></span>
                                <span class="badge pull-left badgeDate" style=" display: inline-block;"><?=$value['date'];?></span>
								<button class="btn btn-primary btn-signin botonMail" onClick="finish('<?php echo $value['contract'];?>',
								'<?php echo $value['mensajeId'];?>','<?php echo $KtModificada;?>','<?php echo $value['Hc'];?>','<?php echo $value['hB'];?>',
								'<?php echo $value['signKey'];?>','<?php echo $value['ciphKey'];?>','<?php echo $value['address_remitente'];?>','<?php echo $value['dest_address'];?>',
								'<?php echo $value['idUser'];?>','<?php echo $value['email'];?>')"> Finalizar </button>
                            </a>

						<?php
						}
						}
						?>
                        </div>
                    </div>
                    <div class="tab-pane fade in" id="profile">
                        <div class="list-group">
                            <div class="list-group-item">
                                <span class="text-center">This tab is empty.</span>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade in" id="messages">
                        ...
                    </div>
                    <div class="tab-pane fade in" id="settings">
                        This tab is empty.
                    </div>
                </div>
            </div>
        </div>
    </div>


</body>
</html>
