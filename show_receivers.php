<?php
	session_start();
	require_once('funciones.php');

	if(isset($_POST['mensajeId'])){
		$conn=establecer_conexion();
		$mensajeId=$_POST['mensajeId'];
		//Debemos obtener los distintos receptores del mensaje
		$sql="SELECT distinct usuario.Id AS idUser, usuario.address, mensaje.Bfin, usuario.email,usuario.signKey, rem_variables.clave_Enviada, rem_variables.hB, 
		rem_variables.Ka, mensaje.c, mensaje.Kt, mensaje.date FROM usuario INNER JOIN rem_variables ON rem_variables.address_destinatario=usuario.address 
		INNER JOIN mensaje ON mensaje.Id=rem_variables.Id_mensaje WHERE mensaje.Id='$mensajeId'";
		
		$run = mysqli_query($conn,$sql);
		while($resultado=mysqli_fetch_assoc($run)){
			$salida[]=$resultado;
		}
		if (isset($salida)){
			foreach($salida as $value){
				//Necesario quitar todos los caracteres " para poder enviarlo a las funciones de Javascript
				$ctModificado = str_replace('"','¬',$value['c']);
				$KtModificada  =str_replace('"','¬',$value['Kt']);
				if($value['hB']=='Cancelled'){
					//Mostrar estado cancelado
					echo "<a href='#' class='list-group-item'>
							<span class='name' style='min-width: 120px;
							display: inline-block;'><b>Remitente: </b>".$value['email']." </span> <span></span>
							<span class='name pull-right' color='red' style='display: inline-block;'><b>Cancelado</b></button>

					</a>";
					
				}else if($value['hB']!='undefined'&&$value['clave_Enviada']=='0'){
					echo "<a href='#' class='list-group-item'>
							<span class='name' style='min-width: 120px;
							display: inline-block;'><b>Remitente: </b>".$value['email']." </span> <span></span>
							<button class='btn btn-primary btn-signin botonMail' onClick=enviarClave('".$ctModificado ."','".$KtModificada ."','".$value['hB'] ."','".$value['Bfin'] ."','".$value['email']."','".$value['Ka']."','".$value['signKey']."','".$mensajeId ."','".$value['idUser']."','".$value['address']."')>Enviar Clave</button>
							<button class='btn btn-primary btn-signin botonMail' onClick=verhB('". $value['hB'] ."')>Ver confirmacion</button>
						</a>";
				}else if ($value['hB']!='undefined'&&$value['clave_Enviada']=='1'){
					//Ver hB
					echo "<a href='#' class='list-group-item'>
							<span class='name' style='min-width: 120px;
							display: inline-block;'><b>Remitente: </b>".$value['email']." </span> <span></span>
							<button class='btn btn-primary btn-signin botonMail' onClick=verhB('". $value['hB'] ."')>Ver confirmacion</button>
					</a>";
				}else{
					echo "<a href='#' class='list-group-item'>
							<span class='name' style='min-width: 120px;
							display: inline-block;'><b>Remitente: </b>".$value['email']." </span> <span></span>
							<span class='name pull-right' color='red' style='display: inline-block;'><b>Esperando confirmación </b></button>

					</a>";
				}
			}
		}else{
			echo "error";
		}
	}
?>