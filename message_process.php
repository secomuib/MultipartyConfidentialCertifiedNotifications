<?php
	session_start();
	require_once 'funciones.php';
	if(isset($_POST['consulta'])){
		$ttp_intr=($_POST['ttp']);
		$conn=establecer_conexion();
		$sql = "SELECT * FROM ttp WHERE email='$ttp_intr'";
		$run = mysqli_query($conn,$sql);
		$row = $run ->fetch_assoc();
		if(isset($row['Id'])){
			$_SESSION['ttp_used']=$row['Id'];
			echo $row['ciphKey'];	
		}else{
			echo "error";
		}	
	}else{
		$ciphertext = ($_POST['c']);
		$hashCiphertext = ($_POST['H(c)']);
		$receptores = ($_POST['dest']);
		$ttp_intr = ($_POST['ttp']);
		$encryptedKey = ($_POST['Kt']);
		$signedMessage = ($_POST['hA']);
		$signedKey = ($_POST['Ka']);
		$addressSender = ($_POST['address']);
		$B = ($_POST['B']);
		$conn = establecer_conexion();
		//Publicamos el mensaje
		$date = date('Y-m-d');
		$ttp = $_SESSION['ttp_used'];
		$sqlMsg = "INSERT INTO mensaje (Id_ttp,address_remitente,c,Hc,B,Kt,date) VALUES ('$ttp','$addressSender','$ciphertext','$hashCiphertext','$B','$encryptedKey','$date')";
		if($conn->query($sqlMsg)==TRUE){
			$id_mensaje=$conn->insert_id;
			echo "ok";
		}else{
			echo "Error: " . $sqlMsg . "<br>" . $conn->error;
		}

		//A continuación debemos crear los parámetros de emisor y receptores
		for ($i=0;$i<count($receptores); ++$i){
			$receptor = $receptores[$i];
			//Comprobamos que el destinatario existe
			$sql = "SELECT * FROM usuario WHERE email='$receptor'";
			$run = mysqli_query($conn,$sql);
			$row = $run->fetch_assoc();
			//Si el correo existe y el mensaje ha sido introducido correctamente en la BD:
			if((mysqli_num_rows($run)>0)&&(isset($id_mensaje))){
				$idReceiver = $row['Id'];
				$addressReceiver = $row['address'];
				//Guardamos las variables del remitente
				$id = $_SESSION['user_session'];
				$sqlRem = "INSERT INTO rem_variables (Id_mensaje,Id_usuario,address_destinatario,hA,Ka,hB,clave_Enviada) VALUES ('$id_mensaje','$id','$addressReceiver','$signedMessage','$signedKey','undefined','false')";
				if($conn->query($sqlRem)==TRUE){
					$id_rem_var=$conn->insert_id;
				}else{
					echo "ERROR rem_variables";
				}

				//Enviamos las variables al destinatario
				$sqlDest =  "INSERT INTO dest_variables (Id_mensaje,Id_usuario,hB,hA,Ka,hB_enviado) VALUES ('$id_mensaje','$idReceiver','undefined','$signedMessage','undefined','false')";
				if($conn->query($sqlDest)==TRUE){
					$id_dest_var=$conn->insert_id;
					echo $id_dest_var;
				}else{
					echo "Error: " . $sql . "<br>" . $conn->error;
				}
			}
		}
	}
?>
