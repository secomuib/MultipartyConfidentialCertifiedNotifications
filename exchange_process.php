<?php
	session_start();
	require_once('funciones.php');

	if(isset($_POST['hB'])){
		//Significa que estamos en el segundo paso del intercambio de parametros (envío de hB)
		$conn=establecer_conexion();
		$mensajeId=$_POST['mensajeId'];
		$hB=$_POST['hB'];
		$id=$_SESSION['user_session'];
		$address = $_POST['address'];
		//Guardamos el contenido en nuestra tabla de variables
		$sql = "UPDATE dest_variables INNER JOIN mensaje ON mensaje.Id=dest_variables.Id_mensaje SET hB='$hB',hB_enviado='1' WHERE mensaje.Id='$mensajeId' AND dest_variables.Id_usuario='$id'";
		if($conn->query($sql)==TRUE){
			//Si ha sido correcto, enviamos el contenido a la tabla del remitente, siempre y cuando no este cancelado ya el intercambio:
			$sql= "UPDATE rem_variables INNER JOIN mensaje ON mensaje.Id=rem_variables.Id_mensaje SET hB='$hB' WHERE mensaje.Id='$mensajeId' AND rem_variables.address_destinatario='$address' AND rem_variables.hB!='Cancelled'";
			if($conn->query($sql)==TRUE){
				echo "ok";
			}else{
		       echo "Error: " . $sql . "<br>" . $conn->error;
			}
		}else{
		   echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}else if (isset($_POST['envioClave'])){
		//Significa que estamos en la tercera fase del intercambio de parametros (envío de Ka)
		$conn=establecer_conexion();
		$mensajeId=$_POST['mensajeId'];
		$idUser=$_POST['idUser'];
		$addressDest = $_POST['addressDest'];
		$Bfin = $_POST['Bfin'];
		$Ka = $_POST['Ka'];
		$sql = "UPDATE dest_variables INNER JOIN mensaje ON mensaje.Id=dest_variables.Id_mensaje SET dest_variables.Ka = (case
                  when dest_variables.Ka='undefined' then '$Ka' else dest_variables.Ka end), mensaje.Bfin='$Bfin' WHERE mensaje.Id='$mensajeId' AND dest_variables.Id_usuario='$idUser'";
		if($conn->query($sql)==TRUE){
			$sql = "UPDATE rem_variables INNER JOIN mensaje ON mensaje.Id=rem_variables.Id_mensaje SET rem_variables.clave_Enviada='1' WHERE mensaje.Id='$mensajeId' AND rem_variables.address_destinatario='$addressDest'";
			if($conn->query($sql)==TRUE){
				echo "ok";
			}else{
			  echo "Error: " . $sql . "<br>" . $conn->error;			
			}
		}else{
			  echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}
	
?>