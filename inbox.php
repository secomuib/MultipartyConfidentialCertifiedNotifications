<?php
session_start();
require_once('funciones.php');
if(!isset($_SESSION['user_session'])){
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

	 <script src="js/main.js"></script>

    <link rel="stylesheet" type="text/css" href="css/main.css">
	<script>

	    var Web3 = require('web3');
        if (typeof web3 !== 'undefined') {
            web3 = new Web3(web3.currentProvider);
        } else {
            web3 = new Web3(new Web3.providers.HttpProvider("http://localhost:8545"));
        }
        web3.eth.getAccounts(function (error, accounts) {
            web3.eth.defaultAccount = accounts[0];
            console.log(web3.eth.defaultAccount);
        });


	//Función para enviar un Mensaje:

	function enviaMensaje(destinatarios, ttp, mensaje) {
    if (web3.eth.defaultAccount != 'undefined') {
		console.log(destinatarios);
		//Obtenemos el array con todos los receptores y quitamos todos los posibles espacios en blanco:
		var arrayDestinatarios = destinatarios.split(",");
		for(var i = 0; i < arrayDestinatarios.length; i++){
			arrayDestinatarios[i]=arrayDestinatarios[i].trim();
		}

        //Posteriormente debemos obtener la clave pública de la TTP.
        var dataConsulta = {
            "ttp": ttp,
            "consulta": true
        };

        $.ajax({
            data: dataConsulta,
            url: 'message_process.php',
            type: 'POST',
            beforeSend: function () {
            },

            success: function (response) {
                if (response != "error") {
                    //Guardamos la clave pública de la TTP
                    var ttpPubKey = response;
					console.log("Clave pública de la TTP");
					console.log(ttpPubKey);
                    ttpPubKey = new sjcl.ecc.elGamal.publicKey(
						sjcl.ecc.curves.c256,
						sjcl.codec.base64.toBits(ttpPubKey)
					);

                    //Obtenemos nuestra clave privada para firmar:
                    var signPrivKey = localStorage.getItem("privSign" + "<?php echo $_SESSION['user_session']; ?>");
                    signPrivKey = new sjcl.ecc.ecdsa.secretKey(
						sjcl.ecc.curves.c256,
						sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.base64.toBits(signPrivKey))
					);
					console.log("Clave privada propia para firmas: ");
                    console.log(signPrivKey);

                    //Generamos clave simétrica de 256 bits y ciframos el mensaje con ella

                    var symKey = sjcl.random.randomWords(8);

                    var ciphertext = sjcl.encrypt(symKey, mensaje);
					console.log("Texto Cifrado: ");
                    console.log(ciphertext);

                    //Ciframos la clave simétrica con la clave pública de la TTP:

                    var encSymKey = sjcl.encrypt(ttpPubKey, sjcl.codec.base64.fromBits(symKey));

                    //Realizamos el Hash del mensaje cifrado y posteriormente lo firmamos concatenado junto a la clave cifrada
                    var hc = sjcl.hash.sha256.hash(ciphertext);
                    hc = sjcl.codec.base64.fromBits(hc);
                    var hA = signPrivKey.sign(sjcl.hash.sha256.hash(hc + destinatarios + encSymKey));
                    hA = sjcl.codec.base64.fromBits(hA);
					console.log("Hash del mensaje y Clave firmados: ");
                    console.log(hA);

                    //Finalmente guardamos la clave generada sin firmar:

                    /*var hashClave = sjcl.hash.sha256.hash(symKey);*/
                    /*var firmaClave = signPrivKey.sign(hashClave);
                    firmaClave = sjcl.codec.base64.fromBits(firmaClave);*/
                    var Ka = sjcl.codec.base64.fromBits(symKey);
                    /*Ka = Ka.concat("FIRMA:" + firmaClave);*/
					/*console.log("Clave simétrica firmada: ")
                    console.log(Ka);*/

                    var data = {
                        "c": ciphertext,
                        "H(c)": hc,
                        "dest": arrayDestinatarios,
                        "ttp": ttp,
						"B": destinatarios,
                        "Kt": encSymKey,
                        "hA": hA,
                        "Ka": Ka,
                        "address": web3.eth.defaultAccount
                    };

                    $.ajax({
                        data: data,
                        url: 'message_process.php',
                        type: 'POST',
                        beforeSend: function () {

                        },
                        success: function (response) {
                            $(function () {
								$('#modal').modal('toggle');
							});
							setTimeout('window.location.href= "inbox.php?enviados=true"',300);
                        }
                    });
					
                } else {
                    alert("Error la TTP seleccionada no existe");
                }
            }
        });
    } else {
        alert('Inicia sesión en METAMASK');
    }
}


//Función encargada de enviar hB al emisor para confirmar la recepción del mensaje:

function recibir(c,B, Kt, hA, remPubKey, mensajeId) {
    //Recuperamos los valores originales de c y Kt:
    c = c.replace(/¬/g, '"');
    Kt = Kt.replace(/¬/g, '"');

    //Comprobamos que la firma del emisor sea correcta:

    var hc = sjcl.hash.sha256.hash(c);
    hc = sjcl.codec.base64.fromBits(hc);
    var remPubKey = new sjcl.ecc.ecdsa.publicKey(sjcl.ecc.curves.c256, sjcl.codec.base64.toBits(remPubKey));
	console.log(hA);
	console.log(hc);	
	console.log(Kt);
    var confirmacion = remPubKey.verify(sjcl.hash.sha256.hash(hc + B + Kt), sjcl.codec.base64.toBits(hA));
    if (confirmacion) {
        //Si la confirmación es correcta debemos firmar el mensaje con nuestra clave privada y guardarlo:

        //Obtenemos nuestra clave privada:
        var signPrivKey = localStorage.getItem("privSign" + "<?php echo $_SESSION['user_session']; ?>");
        signPrivKey = new sjcl.ecc.ecdsa.secretKey(
            sjcl.ecc.curves.c256,
            sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.base64.toBits(signPrivKey))
        );
        //Firmamos la concatenacion de hc y Kt
        var hB = signPrivKey.sign(sjcl.hash.sha256.hash(hc + Kt));
        hB = sjcl.codec.base64.fromBits(hB);

        //Guardamos en la base de datos hB:
        var data = {
            "mensajeId": mensajeId,
			"address": web3.eth.defaultAccount,
            "hB": hB
        };
        $.ajax({
            data: data,
            url: 'exchange_process.php',
            type: 'POST',
            beforeSend: function () {

            },
            success: function (response) {
				if(response=="ok"){
					setTimeout('window.location.href= "inbox.php"',300);
				}
            }
        });

    } else {
        alert('LA FIRMA NO ES CORRECTA');
    }
}

//Función para ver los receptores de un mensaje:

function verReceptores(mensajeId){
		var data = {
			"mensajeId": mensajeId
		}
		$.ajax({
			data: data,
            url: 'show_receivers.php',
            type: 'POST',
			beforeSend: function () {
            },
            success: function (response) {
				if(response!="error"){
					$('#modalBody').append(response);
					$('#myModal3').modal('show');
					$("#myModal3").on("hidden.bs.modal", function(){
					$("#modalBody").html("");
					});

				}
			}	
        });
}


//Funcion para enviar la clave firmada al usuario:

function enviarClave(c, Kt, hB, Bfin,emailDest,Ka,destPubKey, mensajeId,idDest,addressDest) {
    c = c.replace(/¬/g, '"');
    Kt = Kt.replace(/¬/g, '"');
	Bfin = Bfin.concat(emailDest);
    //Comprobamos que la firma del receptor sea correcta:
    var hc = sjcl.hash.sha256.hash(c);
    hc = sjcl.codec.base64.fromBits(hc);
    var destPubKey = new sjcl.ecc.ecdsa.publicKey(sjcl.ecc.curves.c256, sjcl.codec.base64.toBits(destPubKey));
    var confirmacion = destPubKey.verify(sjcl.hash.sha256.hash(hc + Kt), sjcl.codec.base64.toBits(hB));

	//Firma Ka:
	var signPrivKey = localStorage.getItem("privSign" + "<?php echo $_SESSION['user_session']; ?>");
    signPrivKey = new sjcl.ecc.ecdsa.secretKey(
		sjcl.ecc.curves.c256,
		sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.base64.toBits(signPrivKey))
	);
	var hashClave = sjcl.hash.sha256.hash(Ka + Bfin);
    var firmaClave = signPrivKey.sign(hashClave);
    firmaClave = sjcl.codec.base64.fromBits(firmaClave);
    Ka = Ka.concat("FIRMA:" + firmaClave);

    if (confirmacion) {
        var data = {
            "envioClave": true,
			"Ka": Ka,
			"Bfin": Bfin,
			"mensajeId": mensajeId,
			"idUser": idDest,
			"addressDest": addressDest
        }
        $.ajax({
            data: data,
            url: 'exchange_process.php',
            type: 'POST',
            beforeSend: function () {

            },
            success: function (response) {
                console.log(response);
            }
        });
    } else {
        alert('LA FIRMA NO ES CORRECTA');
    }
}

//Funcion para leer un mensaje

function leer(c, Ka,Bfin, remPubKey, ttpPubKey, email, date,emailDest) {
    c = c.replace(/¬/g, '"');
    var splitClave = Ka.split("FIRMA:");
	Bfin = Bfin.substring(0, Bfin.indexOf(emailDest) + emailDest.length);
	console.log(Bfin);
	console.log(ttpPubKey);
	console.log(emailDest);
	console.log(email);
		try{
				var remPubKey = new sjcl.ecc.ecdsa.publicKey(sjcl.ecc.curves.c256, sjcl.codec.base64.toBits(remPubKey));
				var confirmacion = remPubKey.verify(sjcl.hash.sha256.hash(splitClave[0]+Bfin), sjcl.codec.base64.toBits(splitClave[1]));
				//Si la confirmación es correcta, desciframos el mensaje:
				var plaintext = sjcl.decrypt(sjcl.codec.base64.toBits(splitClave[0]), c);
				$('#titulo1').html("Remitente");
				$('#input1').val(email);
				$('#input1').prop("readonly",true);
				$('#titulo2').html("Fecha");
				$('#input2').val(date);
				$('#input2').prop("readonly",true);
				$('#input3').val(plaintext);
				$('#input3').prop("readonly",true);
				$('#myModal2').modal('show');
			}catch(err){
				try{
					console.log("He entrado aqui");
					console.log(splitClave[0]);
					var ttpPubKey = new sjcl.ecc.ecdsa.publicKey(sjcl.ecc.curves.c256, sjcl.codec.base64.toBits(ttpPubKey));
					var confirmacion = ttpPubKey.verify(sjcl.hash.sha256.hash(splitClave[0]+emailDest), sjcl.codec.base64.toBits(splitClave[1]));
					//Si la confirmación es correcta, desciframos el mensaje:
					var plaintext = sjcl.decrypt(sjcl.codec.base64.toBits(splitClave[0]), c);
					$('#titulo1').html("Remitente");
					$('#input1').val(email);
					$('#input1').prop("readonly",true);
					$('#titulo2').html("Fecha");
					$('#input2').val(date);
					$('#input2').prop("readonly",true);
					$('#input3').val(plaintext);
					$('#input3').prop("readonly",true);
					$('#myModal2').modal('show');
				}catch(err){
				alert('La firma no es correcta');
				}
			}
	
}

//Función para cancelar

function cancelar(contractAddress,mensajeId) {
    var emailContract = loadContract(contractAddress);
		
	var cancelEvent = emailContract.cancelEvent();


	//Hacemos una petición a la base de datos para obtener el array de usuarios que cancelaremos:
	var data = {
		"consultaUsuarios": true,
		"mensajeId": mensajeId
	}
	var usuariosCancelados = new Array();
	var hBUsuarios = new Array();
	$.ajax({
		data: data,
		url: 'cancel_process.php',
		type: 'POST',
		beforeSend: function () {
        },
        success: function (response) {
			if(response!="error"){
				var arrayDestinatarios = JSON.parse(response);
				cancelEvent.watch(function (error, result) {
					if (result) {
						usuariosCancelados.push(result.args.cancelledUser);
						hBUsuarios.push(result.args.cancelResponse);
						//En caso de que las longitudes de los arrays sean iguales,
						//significa que ya se ha llevado a cabo la cancelación para todos los usuarios
						if(usuariosCancelados.length==arrayDestinatarios.length){
							var data = {
								"arrayUsuarios": JSON.stringify(usuariosCancelados),
								"arrayhB": JSON.stringify(hBUsuarios),
								"mensajeId": mensajeId
							}
							$.ajax({
								data: data,
								url: 'cancel_process.php',
								type: 'POST',
								beforeSend: function () {
								},
								success: function (response) {
									//Actualizar:
									console.log(response);
								}
							});
						}	    
					}
				});
					emailContract.cancel(mensajeId,arrayDestinatarios,(err, res) => {
						if (err) {
							alert(err);
						}
					});
			}
		}
    });
}

//Función para cargar el contrato
function loadContract(contractAddress) {
    var contract = web3.eth.contract(abi);
    var emailContract = contract.at(contractAddress);
    return emailContract;
}

//Función que permite al receptor acudir a la TTP para obtener la clave
function finalizar(mensajeId,hc,Kt,hA,hB){

		Kt = Kt.replace(/¬/g, '"');

		var signPrivKey = localStorage.getItem("privSign" + "<?php echo $_SESSION['user_session']; ?>");
        signPrivKey = new sjcl.ecc.ecdsa.secretKey(
			sjcl.ecc.curves.c256,
			sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.base64.toBits(signPrivKey))
		);
		//Realizamos el Hash del mensaje cifrado y posteriormente lo firmamos concatenado junto a la clave cifrada
			
		var hashIntervencion = sjcl.hash.sha256.hash(hc,Kt,hA,hB,mensajeId);
        hashIntervencion = sjcl.codec.base64.fromBits(hashIntervencion);
        var solicitudFirmada = signPrivKey.sign(hashIntervencion);
        solicitudFirmada = sjcl.codec.base64.fromBits(solicitudFirmada);
		
		var data = {
			"mensajeId": mensajeId,
			"hbit": solicitudFirmada
        }

        $.ajax({
            data: data,
            url: 'final_process.php',
            type: 'POST',
            beforeSend: function () {

            },
            success: function (response) {
						if(response=="ok"){
							setTimeout('window.location.href= "inbox.php"',300);
						}
			}
        });
}

function verhB(hB){
	$('#hBinput').val(hB);
	$('#hBinput').prop("readonly",true);
	$('#myModal4').modal('show');
}

function verOrigen(hA,Ka){
	$('#hBinput').append("Prueba 1:\n");
	$('#hBinput').append(hA + "\n\n");
	var splitClave = Ka.split("FIRMA:");
	$('#hBinput').append("Prueba 2: \n");
	$('#hBinput').append(splitClave[1]);
	$('#hBinput').prop("readonly",true);
	$('#modalTitle').html("Justificante de Origen");
	$('#myModal4').modal('show');
}

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
                <a href="#myModal" data-toggle="modal" class="btn btn-danger btn-sm btn-block" role="button">REDACTAR</a>
                <hr />
                <ul class="nav nav-pills nav-stacked">

                    <!-- MODAL -->
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="myModal" class="modal fade" style="display: none;">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                    <h4 class="modal-title">Escriba su mensaje</h4>
                                </div>
                                <div class="modal-body">
                                    <form role="form" class="form-horizontal">
                                        <div class="form-group">
                                            <label class="col-lg-2 control-label" id="destinatario">Destinatario</label>
                                            <div class="col-lg-10">
                                                <input type="text" placeholder="" id="inputReceptor" class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-lg-2 control-label" id="ttp">TTP</label>
                                            <div class="col-lg-10">	
                                                <input type="text" placeholder="" id="inputTTP" class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-lg-2 control-label" id="mensaje">Mensaje</label>
                                            <div class="col-lg-10">
                                                <textarea rows="10" cols="30" class="form-control" id="inputMessage" name=""></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-lg-offset-2 col-lg-10">
                                                <input class="btn btn-send" type="button" id="enviar" value="Enviar" onclick="enviaMensaje($('#inputReceptor').val(),$('#inputTTP').val(),$('#inputMessage').val())" />
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->

					 <!-- MODAL -->
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="myModal2" class="modal fade" style="display: none;">
                        <div class="modal-dialog" >
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                    <h4 class="modal-title">Mensaje</h4>
                                </div>
                                <div class="modal-body">
                                    <form role="form" class="form-horizontal">
                                        <div class="form-group">
                                            <label class="col-lg-2 control-label" id="titulo1">Destinatario</label>
                                            <div class="col-lg-10">
                                                <input type="text" placeholder="" id="input1" class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-lg-2 control-label" id="titulo2">TTP</label>
                                            <div class="col-lg-10">	
                                                <input type="text" placeholder="" id="input2" class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-lg-2 control-label" id="titulo3">Mensaje</label>
                                            <div class="col-lg-10">
                                                <textarea rows="10" cols="30" class="form-control" id="input3" name=""></textarea>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->

					<!-- MODAL -->
                    <div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="myModal3" class="modal fade" style="display: none;">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header" id="modalHeader">
                                    <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                    <h4 class="modal-title">RECEPTORES</h4>
                                </div>
                                <div class="modal-body" id="modalBody">
                                    
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->

					<div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="myModal4" class="modal fade" style="display: none;">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header" id="modalHeader4">
                                    <button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button>
                                    <h4 class="modal-title" id="modalTitle">Justificante de recepción</h4>
                                </div>
                                <div class="modal-body" id="modalBody4">
									<textarea rows="10" cols="30" class="form-control" id="hBinput" name=""></textarea>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->


					<?php
					if(isset($_GET['enviados'])){
					?>
					<li class=""><a href="inbox.php"> Inbox </a></li>
                    <li class="active"><a href="inbox.php?enviados=true">Enviados</a></li>
					<?php
					}else{
					?>
					<li class="active" id="inbox"><a href="inbox.php"> Inbox </a></li>
					<li class=""><a href="inbox.php?enviados=true">Enviados</a></li>
					<?php
					}
					?>
                </ul>
            </div>
            <div class="col-sm-9 col-md-10">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#home" data-toggle="tab">
                            <span class="glyphicon glyphicon-inbox">
                            </span>Primary
                        </a>
                    </li>
                </ul>
                <!-- Tab panes -->
                <div class="tab-content">
                    <div class="tab-pane fade in active" id="home">
                        <div class="list-group">
							<?php
							//Si estamos en INBOX solo mostramos los mensajes recibidos:
							if(!isset($_GET['enviados'])){
							$conn = establecer_conexion();
							$idUser = $_SESSION['user_session'];
							$address = $_SESSION['address'];
							$sql="SELECT * FROM usuario WHERE id='$idUser'";
							$run = mysqli_query($conn,$sql);
							$row = $run->fetch_assoc();
							$emailDest = $row['email'];

							//Obtenemos todos los parámetros necesarios de los distintos mensajes:
							$sql = "SELECT distinct mensaje.Id AS mensajeId, mensaje.c, mensaje.Bfin ,mensaje.Hc,usuario.Id,mensaje.B,usuario.email,usuario.signKey,mensaje.date, mensaje.Kt,dest_variables.hA, 
									dest_variables.Ka, dest_variables.hB , dest_variables.hB_enviado, ttp.signKey AS ttpSignKey FROM mensaje INNER JOIN dest_variables ON dest_variables.Id_mensaje= mensaje.Id 
									INNER JOIN rem_variables ON mensaje.Id=rem_variables.Id_mensaje INNER JOIN usuario ON rem_variables.Id_usuario=usuario.Id INNER JOIN ttp ON
									ttp.Id=mensaje.Id_ttp WHERE dest_variables.Id_usuario='$idUser' AND rem_variables.address_destinatario='$address' ORDER BY mensaje.Id DESC";
							$run = mysqli_query($conn,$sql);
							while($resultado=mysqli_fetch_assoc($run)){
								$salida[]=$resultado;
							}
							if (isset($salida)){
								foreach($salida as $value){
									//Necesario quitar todos los caracteres " para poder enviarlo a la funcion recibir();
									$ctModificado = str_replace('"','¬',$value['c']);
									$KtModificada  =str_replace('"','¬',$value['Kt']);
									$mensajeId = $value['mensajeId'];
									
									if($value['Ka']=='Cancelled'){
							?>
									<a href="#" class="list-group-item">
										<span class="name" style="min-width: 120px;
										display: inline-block;"><b>Sender: </b> <?=$value['email']?> </span> <span class=""><b></span>
										<span class="badge pull-left badgeDate" style=" display: inline-block;"><?=$value['date']?></span>
										<span class="name pull-right" color="red" style="display: inline-block;">Cancelado por emisor</button>
										</a>
							<?php
									}else if(($value['hB_enviado']=='1')&&($value['Ka']=='undefined')){ //Si se ha enviado hB al remitente y aun no hemos recibido la clave, podemos finalizar el intercambio
							?>
										<a href="#" class="list-group-item">
										<span class="name" style="min-width: 120px;
										display: inline-block;"><b>Sender: </b> <?=$value['email']?> </span> <span class=""></span>
										<span class="badge pull-left badgeDate" style=" display: inline-block;"><?=$value['date']?></span>
										<button class="btn btn-primary btn-signin botonMail" onClick="finalizar('<?php echo $mensajeId;?>','<?php echo $value['Hc'];?>','<?php echo $KtModificada;?>','<?php echo $value['hA'];?>','<?php echo $value['hB'];?>')">Finalizar</button>
										</a>
							<?php
									}else if ($value['Ka']=='pending'){ //El mensaje se encuentra a la espera de la intervencion de la TTP	
							?>
							
										<a href="#" class="list-group-item">
										<span class="name" style="min-width: 120px;
										display: inline-block;"><b>Sender: </b> <?=$value['email']?> </span> <span class=""></span>
										<span class="badge pull-left badgeDate" style=" display: inline-block;"><?=$value['date']?></span>
										<span class="name pull-right" color="red" style="display: inline-block;">Esperando finalizacion</button>
										</a>
							<?php
									}else if($value['Ka']=='undefined'){ //Si no hemos recibido la clave debemos enviar hB para recibir la clave
							?>
										<a href="#" class="list-group-item">
										<span class="name" style="min-width: 120px;
										display: inline-block;"><b>Sender : </b> <?=$value['email']?>  </span> <span class=""></span>
										<span class="badge pull-left badgeDate" style=" display: inline-block;"><?=$value['date']?></span>
										<button class="btn btn-primary btn-signin botonMail" onClick="recibir('<?php echo $ctModificado;?>','<?php echo $value['B'];?>','<?php echo $KtModificada;?>','<?php echo $value['hA'];?>','<?php echo $value['signKey'];?>','<?php echo $value['mensajeId'];?>')">Recibir</button>
										</a>
							<?php
									}else{		//Si no se cumple ninguna de las condiciones anteriores significa que tenemos Ka y que podemos leer el mensaje
							?>
										<a href="#" class="list-group-item">
										<span class="name" style="min-width: 120px;
										display: inline-block;"><b>Sender: </b> <?=$value['email']?> </span> <span class=""></span>
										<span class="badge pull-left badgeDate" style=" display: inline-block;"><?=$value['date']?></span>
										<button class="btn btn-primary btn-signin botonMail" onClick="verOrigen('<?php echo $value['hA'];?>','<?php echo $value['Ka'];?>')">No repudio</button>
										<button class="btn btn-primary btn-signin botonMail" onClick="leer('<?php echo $ctModificado;?>','<?php echo $value['Ka'];?>','<?php echo $value['Bfin'];?>','<?php echo $value['signKey'];?>','<?php echo $value['ttpSignKey'];?>','<?=$value['email']?>','<?=$value['date']?>','<?php echo $emailDest;?>')">Leer</button>
										</a>
							<?php
									}
								}
							}
						}else{
							//Mensajes Enviados	
							$conn = establecer_conexion();
							$idUser = $_SESSION['user_session'];
							$sql = "SELECT distinct mensaje.Id AS mensajeId, mensaje.date,ttp.email, ttp.contract FROM `mensaje` INNER JOIN rem_variables ON mensaje.Id=rem_variables.Id_mensaje 
									INNER JOIN ttp ON ttp.Id=mensaje.Id_ttp WHERE rem_variables.Id_usuario='$idUser' ORDER BY mensaje.Id DESC";
							$run = mysqli_query($conn,$sql);
							while($resultado=mysqli_fetch_assoc($run)){
								$salida[]=$resultado;
							}
							if (isset($salida)){
								foreach($salida as $value){
								$mensajeId = $value['mensajeId'];
								$contract = $value['contract'];
							?>
							
							<a href="#" class="list-group-item">
                                <span class="name" style="min-width: 120px;
                                display: inline-block;"><b>TTP: </b> <?=$value['email']?> </span> <span class="">
                                <span class="badge pull-left badgeDate" style=" display: inline-block;"><?=$value['date']?></span>
								<button class="btn btn-primary btn-signin botonMail" onClick="cancelar('<?php echo $contract;?>','<?php echo $mensajeId;?>')">Cancelar</button>
								<button class="btn btn-primary btn-signin botonMail" onClick="verReceptores('<?php echo $mensajeId;?>')">Gestionar Receptores</button>

                            </a>

						<?php								
						}
						}
						}
						?>
                        </div>
                    </div>
                   
                </div>
            </div>
        </div>
    </div>


</body>
</html>
