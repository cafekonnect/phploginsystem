<?php include("include/header.php") ?>
<?php include("include/nav.php") ?>



	<div class="jumbotron">
		<h1 class="text-center"><?php if(logged_in()){
			echo "Logged in"; }else{
				redirect("index.php");
			}?> Admin</h1>
	</div>


	
<?php include("include/footer.php") ?>