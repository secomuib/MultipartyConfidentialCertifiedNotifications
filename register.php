<!DOCTYPE html>
<html lang="es">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta charset="utf-8">
	   
	<!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	
	<!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	
    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<!-- Web3 -->
	<script src="./node_modules/web3/dist/web3.min.js"></script>	
	
	<!-- stanford cryptography library -->
	 <script src="https://bitwiseshiftleft.github.com/sjcl/sjcl.js"></script>
	 <script type="text/javascript" src="js/sjcl.js"></script>

	<!-- Script with the ABI var from the contract -->
	<script type="text/javascript" src="js/abi.js"></script> 

	<link rel="stylesheet" type="text/css" href="css/login.css">
    <script>
        //Al cargar la página detectamos que el usuario tenga metamask activado.

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


        function enviaParametros(email, contraseña,confirmacion,ttp) {
			
			if (contraseña != confirmacion) {    //Si las contraseñas no coinciden avisamos al usuario
			    alert('Las contraseñas no coinciden');
			} else if (typeof web3.eth.defaultAccount == 'undefined') { //Si el usuario no tiene metamask activado con alguna cuenta por defecto le indicamos que es necesario.
				alert('Es necesario activar Metamask para el registro')
			} else {        //Si todo es correcto debemos registrar al usuario:

				//En primer lugar debemos generar la clave pública y privada que utilizará el usuario:
			    var pairSign = sjcl.ecc.ecdsa.generateKeys(256);    //Generación del par de claves de firma
		        var pairCiph = sjcl.ecc.elGamal.generateKeys(256)   //Generación del par de claves de cifrado

                var pubSign = pairSign.pub.get();
                var privSign = pairSign.sec.get();
                var pubCiph = pairCiph.pub.get();
	            var privCiph = pairCiph.sec.get();

               //Serializamos las claves para facilitar su almacenamiento.
               pubSign = sjcl.codec.base64.fromBits(pubSign.x.concat(pubSign.y));
               privSign = sjcl.codec.base64.fromBits(privSign);
               pubCiph = sjcl.codec.base64.fromBits(pubCiph.x.concat(pubCiph.y));
               privCiph = sjcl.codec.base64.fromBits(privCiph)
               console.log("Signing pub key:" + pubSign);
               console.log("Signing priv key: " + privSign);
               console.log("Encrypt pub key: " + pubCiph);
               console.log("Encrypt priv key: " + privCiph);

			   //Debemos comprobar si estamos registrando a una TTP o a un usuario normal
			   if(ttp!=undefined){
					var parametrosRegistro = {
						"email": email,
						"pass": contraseña,
						"pass_conf": confirmacion,
						"signKey": pubSign,
						"ciphKey": pubCiph,
						"ttp": ttp,
						"address": web3.eth.defaultAccount
					};	
			   }else{
					var parametrosRegistro = {
						"email": email,
						"pass": contraseña,
						"pass_conf": confirmacion,
						"signKey": pubSign,
						"ciphKey": pubCiph,
						"address": web3.eth.defaultAccount
					};	
			   }

               $.ajax({
		           data: parametrosRegistro,
                   url: 'register_process.php',
			       type: 'post',
                   beforeSend: function () {
					console.log("Procesando...")
                   },
                   success: function (response) {
						if(!response.includes("Error")){
							//Guardamos las claves privadas en el navegador del usuario:

							//A continuación debemos comprobar si se ha registrado satisfactoriamente una nueva TTP y en cuyo caso debemos hacer el deploy del contrato
							if(ttp!=undefined){
								deploy(response.trim());
								//Guardamos las claves privadas en el navegador de la ttp:
								localStorage.setItem("ttpPrivSign"+response.trim(), privSign);
								localStorage.setItem("ttpPrivCiph"+response.trim(), privCiph);
							}else{
								//Guardamos las claves privadas en el navegador del usuario:
								localStorage.setItem("privSign"+response.trim(), privSign);
								localStorage.setItem("privCiph"+response.trim(), privCiph);
								
								//En caso contrario redirigimos al usuario nuevo a la página de inbox
								setTimeout('window.location.href= "inbox.php"',300);
							}

						}else if(response.includes("for key 'email'")){
							alert("Email ya registrado");
						}else if (response.includes("for key 'address'")){
							alert("Address ya en uso en otra cuenta");
						}
					}
               });
			}
			
        }

		function deploy(idTTP){
		//Abi obtenida a partir de Remix ()
		var contract = web3.eth.contract(abi);	

		//Colgar contrato en blockchain

		var contractInstance = contract.new({
			data: '0x' + bytecode,
			from: web3.eth.coinbase,
			gas: 2591060
			}, (err,res)=>{
				if(err){
					console.log(err);
					return;
				}
				if(res.address!=null){
					console.log(res.address);
					var data = {
						"idTTP": idTTP,
						"contract": res.address
					};
					$.ajax({
						data: data,
						url: 'deploy_process.php',
						type: 'POST',
						beforeSend: function(){
						},
						success: function(response){
							console.log(response);
							if(response=="ok"){
								setTimeout('window.location.href= "ttp.php"',300);
							} 
						}
					});
				}
		});
	}
    </script>

</head>
<body>

    <div class="container">
        <div class="card card-container">
            <img id="profile-img" class="profile-img-card" src="//ssl.gstatic.com/accounts/ui/avatar_2x.png" />
            <p id="profile-name" class="profile-name-card"></p>
			<form id="formRegistro" class="form-signin" action="#" method="post">
				<span id="reauth-email" class="reauth-email"></span>
				<input type="email" name="email" id="inputEmail" class="form-control" placeholder="Correo Electrónico" required autofocus>
				<input type="password" name="pass" id="password" class="form-control" placeholder="Contraseña" required>
				<input type="password" name="pass_conf" id="confirmPassword" class="form-control" placeholder="Confirmar Contraseña" required>
				<label><input type="checkbox" name="ttp" id="ttp" value="ttp"/> Registrarse como TTP</label>
				<p></p>
				<input type="button" class="btn btn-lg btn-primary btn-block btn-signin" id="registerButton" value ="Registrarse" onclick="enviaParametros($('#inputEmail').val(), $('#password').val(), $('#confirmPassword').val(),$('#ttp:checkbox:checked').val())"/>
			</form><!-- /form -->
            <a href="login.php" class="forgot-password">
                Iniciar Sesión
            </a>
        </div><!-- /card-container -->
    </div><!-- /container -->

</body>
</html>