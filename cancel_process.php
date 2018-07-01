<?php
	session_start();
	require_once('funciones.php');

	if(isset($_POST['arrayUsuarios'])){
		$conn=establecer_conexion();
		$mensajeId=$_POST['mensajeId'];
		//Obtenemos los arrays con los usuarios y parámetros hB:
		$arrayUsuarios=json_decode($_POST['arrayUsuarios']);
		$arrayhB=json_decode($_POST['arrayhB']);
		for($i=0;$i<count($arrayUsuarios);++$i){
			//Guardamos el contenido en nuestra tabla de variables
			$usuarioCancelado = $arrayUsuarios[$i];
			$hB = $arrayhB[$i];
			$sql = "UPDATE rem_variables INNER JOIN mensaje ON mensaje.Id=rem_variables.Id_mensaje SET hB='$hB' 
					WHERE mensaje.Id='$mensajeId'AND rem_variables.address_destinatario='$usuarioCancelado'";
			$conn->query($sql);
		}
		echo "ok";
	}else if (isset($_POST['consultaUsuarios'])){
		$conn=establecer_conexion();
		$mensajeId=$_POST['mensajeId'];
		//Guardamos el contenido en nuestra tabla de variables
		$sql = "SELECT distinct rem_variables.address_destinatario FROM rem_variables INNER JOIN mensaje ON mensaje.Id=rem_variables.Id_mensaje 
				WHERE mensaje.Id='$mensajeId' AND rem_variables.hB='undefined'";
		$run = mysqli_query($conn,$sql);
		while($resultado=mysqli_fetch_assoc($run)){
			$salida[]=$resultado;
		}
		$destinatarios = array();
		if (isset($salida)){
			foreach($salida as $value){
				array_push($destinatarios,$value['address_destinatario']);
			}
			echo json_encode($destinatarios);
		}else{
			echo "error";
		}
	}
	
?>