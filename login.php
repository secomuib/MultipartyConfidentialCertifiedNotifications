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

<!-- Web3 -->
	<script src="./node_modules/web3/dist/web3.min.js"></script>	


    <link rel="stylesheet" type="text/css" href="css/login.css">

	<script>

	//Al cargar la página detectamos que el usuario tenga metamask activado:
	var Web3 = require('web3');
    if (typeof web3 !== 'undefined') {
		web3 = new Web3(web3.currentProvider);
	}else{
		web3 = new Web3(new Web3.providers.HttpProvider("http://localhost:8545"));
    }
	web3.eth.getAccounts(function (error, accounts) {
	web3.eth.defaultAccount = accounts[0];
    console.log(web3.eth.defaultAccount);
    });


	function submitForm(email,contraseña){
		var data = {
			"email": email,
			"pass": contraseña,
			"address": web3.eth.defaultAccount
		};

		console.log(data);
		$.ajax({
			data: data,
			url: 'login_process.php',
			type: 'POST',
			beforeSend: function(){
			
			},
			success: function(response){
				if(response=="ttp"){
					//Si no es una TTP le redirige a inbox de usuario
					setTimeout('window.location.href = "ttp.php"; ', 300);
				}else if(response=="user"){
					//Si es una TTP le redirige a la inbox de las TTP
					setTimeout('window.location.href = "inbox.php" ', 300);
				}else{
					alert(response);
				}
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
            <form class="form-signin" id="login-form">
                <span id="reauth-email" class="reauth-email"></span>
                <input type="email" id="user_email" class="form-control" placeholder="Correo Electrónico" required autofocus>
                <input type="password" id="password" class="form-control" placeholder="Contraseña" required>
				<input type="button" class="btn btn-lg btn-primary btn-block btn-signin" id="loginButton" value ="Entrar" onclick="submitForm($('#user_email').val(),$('#password').val())"/>
            </form><!-- /form -->
            <a href="register.php" class="forgot-password">
                Registrarse
            </a>
        </div><!-- /card-container -->
    </div><!-- /container -->


</body>
</html>
