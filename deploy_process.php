<?php
	session_start();
	require_once 'funciones.php';
	if(isset($_POST['contract'])){
	$conn=establecer_conexion();
	$id_TTP=$_POST['idTTP'];
	$contract_address=$_POST['contract'];
	$sql = "UPDATE ttp SET contract='$contract_address' WHERE Id='$id_TTP'";
	if($conn->query($sql)==TRUE){
		echo"ok";
	}else{
       echo "Error: " . $sql . "<br>" . $conn->error;
	}
	$conn->close();
	}
?>
