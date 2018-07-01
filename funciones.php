<?php
      function establecer_conexion(){
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "certified_mail_multipart";

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
        }else{
          return $conn;
        }
      }

?>