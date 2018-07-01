	<?php
		session_start();
		require_once('funciones.php');
		$conn=establecer_conexion();
		
		//Debemos comprobar si se trata de un usuario normal o de una TTP
		if($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST['ttp'])){	
			$email_intr = $_POST["email"];
			$pass_intr = $_POST["pass"];
			$address = $_POST['address'];
			$signKey = $_POST['signKey'];
			$ciphKey = $_POST['ciphKey'];
			//Ponemos el contrato como undefined porque aún no se ha realizado el deploy de este.
			$sql = "INSERT INTO ttp (email,password,address,signKey,ciphKey,contract) VALUES ('$email_intr','$pass_intr','$address','$signKey','$ciphKey','undefined')" ;
			if($conn->query($sql)==TRUE){
				$_SESSION['user_session']=$conn->insert_id;
				$_SESSION['ttp']=false;
				$_SESSION['address']=$_POST['address'];
				echo $_SESSION['user_session'];
			}else{
				echo "Error: " . $sql . "<br>" . $conn->error;
			}
		}else if ($_SERVER["REQUEST_METHOD"]=="POST") {
			$email_intr = $_POST["email"];
			$pass_intr = $_POST["pass"];
			$address = $_POST['address'];
			$signKey = $_POST['signKey'];
			$ciphKey = $_POST['ciphKey'];
			$sql = "INSERT INTO usuario (email,password,address,signKey,ciphKey) VALUES ('$email_intr','$pass_intr','$address','$signKey','$ciphKey')" ;

			if($conn->query($sql)==TRUE){
				$_SESSION['user_session']=$conn->insert_id;
				$_SESSION['ttp']=true;
				$_SESSION['address']=$_POST['address'];				
				echo $_SESSION['user_session'];
			}else{
				echo "Error: " . $sql . "<br>" . $conn->error;
			}
		}
	?>