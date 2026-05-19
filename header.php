<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Sciantec Analytical Proficiency Portal">
	<meta name="keywords" content="Science,Labs,Technology,">
	<meta name="author" content="Alex Fowler">
	<title>Barley Workbook</title>
	<link href="https://fonts.googleapis.com/css?family=Lato:100,300,400,700" rel='stylesheet' type='text/css'>
	<link href="https://portalukgrain.org/css/bootstrap.min.css" rel="stylesheet">   
	<style>
		body {font-family: 'Lato';}
		.fa-btn {margin-right: 6px;}
	</style>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
	<link href="/css/app.css" rel="stylesheet">
</head>
<body id="app-layout" <?php body_class(); ?>>
	<header>
		<div class="container">
		<h1 class="company">Barley Workbook<br/>
			<span><a href="mailto:Portal@ukgrain.org" style="color: #b70833;">Portal@ukgrain.org</a></span>
		</h1>
		 <a href="http://www.uknir.org/" target="_blank" id="logo">
			<img src="/uploads/UK_NIR_Grain_Network.jpg" alt="UK Grain Testing Network Ltd" title="New Site"/>
		</a>
		</div>
		<div id="navbar">
		<div class="container">
			<nav class="pull-right">
				<?php wp_nav_menu( array( 'theme_location' => 'main-menu', 'link_before' => '<span itemprop="name">', 'link_after' => '</span>' ) ); ?>
			</nav>
		</div>
		</div>
	</header>
	<div id="container">
		<main id="content" role="main">