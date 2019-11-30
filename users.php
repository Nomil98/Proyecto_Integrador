<?php
require 'config.php';

if (!isset($_SESSION['loggedin']) or $_SESSION["rol"] !== "ADMIN") {
	header('Location: home.php');
	exit();
}

$users = Database::getArrayBySql(
	"SELECT * FROM accounts"
);
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Home Page</title>
		<link href="style.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
	</head>
	<body class="loggedin">
		<nav class="navtop">
			<div>
				<div class="navbar-brand">
                   <a class="navbar-item title " href="home.php">
                      <img src="images/logo_Smart.png" alt="Logo" width="120" height="30">
                   </a>
                </div>
                <a href="users.php"><i class="fas fa-users"></i>Users</a>
                <a href="graficas.html"><i class="fas fa-chart-bar"></i>Graphics</a>
				<a href="profile.php"><i class="fas fa-user-circle"></i>Profile</a>
				<a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
			</div>
		</nav>
		<div class="content">
			<div>
				
			
			<div align="right">
			<i class="fas fa-user-plus"></i>
			</div>
			<table>
			 	<tr>
			    	<th>Nombre de usuario</th>
			    	<th>Correo electrónico</th>
			    	<th>Opciones</th>
			  	</tr>
			  	<?php foreach ($users as $user): ?>
				  	<tr>
				    	<td><?php echo $user->username; ?></td>
				    	<td><?php echo $user->email; ?></td>
				    	<td>
				    		<button class="btn btn-primary">Editar</button>
				    		<button class="btn btn-danger">Eliminar</button>
				    	</td>
				  	</tr>
				<?php endforeach; ?>
			</table>
		</div>
		</div>
	</body>
</html>