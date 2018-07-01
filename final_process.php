<?php
	session_start();
	require_once('funciones.php');

	$conn=establecer_conexion();
	$mensajeId=$_POST['mensajeId'];
	$idUser = $_SESSION['user_session'];
	if(isset($_POST['Ka'])){	//La Finalización la lleva a cabo la TTP
		$Ka = $_POST['Ka'];
		$idDest = $_POST['idDest'];
		$ttp_inter_result = $_POST['ttp_inter_result'];
		$sql = "UPDATE dest_variables INNER JOIN mensaje ON mensaje.Id=dest_variables.Id_mensaje SET Ka='$Ka', ttp_inter_result='$ttp_inter_result' WHERE mensaje.Id='$mensajeId' AND dest_variables.Id_usuario='$idDest'";
		if($conn->query($sql)==TRUE){
			echo "ok";
		}else{
		   echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}else{
		$hbit= $_POST['hbit'];
		$sql = "UPDATE dest_variables INNER JOIN mensaje ON mensaje.Id=dest_variables.Id_mensaje SET Ka='pending', ttp_inter='$hbit' WHERE mensaje.Id='$mensajeId' AND dest_variables.Id_usuario='$idUser'";
		if($conn->query($sql)==TRUE){
			echo "ok";
		}else{
		   echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}
	
?>