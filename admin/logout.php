<?php
/**
 * ADMIN - Logout
 * 
 * Encerra a sessão do professor e redireciona para o login.
 */
session_start();
session_destroy();
header('Location: index.php');
exit;