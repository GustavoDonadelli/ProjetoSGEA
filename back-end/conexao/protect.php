<?php

if(!isset($_SESSION)) {
  session_start();
}
if(!isset($_SESSION['id'])) {
  die("Acesso nao permitido. Realize o login para acessar a pagina.<p><a href=\"index.php\"></a></p>");
}

?>