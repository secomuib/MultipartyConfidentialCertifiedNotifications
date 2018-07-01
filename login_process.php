<?php
	session_start();
	require_once 'funciones.php';

	if((isset($_POST['email']))&&(isset($_POST['pass']))){
		$email_intr=($_POST['email']);
		$pass_intr = ($_POST['pass']);
		$address = ($_POST['address']);
		$conn=establecer_conexion();
		//Comprobamos si el login es correcto
		$sql="SELECT * FROM usuario WHERE email='$email_intr'";
		$run = mysqli_query($conn,$sql);
		$row = $run->fetch_assoc();
		if($row['password']==$pass_intr && $row['address']==$address){
			$_SESSION['user_session']= $row['Id'];
			$_SESSION['ttp']= false;
			$_SESSION['address']=$row['address'];
			echo "user";
		}else{
			//Comprobamos si se trata de un login de una TTP:
			$sql="SELECT * FROM ttp WHERE email='$email_intr'";
			$run = mysqli_query($conn,$sql);
			$row = $run->fetch_assoc();
			if($row['password']==$pass_intr && $row['address']==$address){
				$_SESSION['user_session']= $row['Id'];
				$_SESSION['ttp']= true;
				$_SESSION['address']=$row['address'];
				echo "ttp";
			}else{
				echo "Error en el login";
			}
		}
	}
?>
