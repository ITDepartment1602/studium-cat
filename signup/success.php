<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Studium</title>
  	<link rel="icon" href="../../img/logo.png">
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<style type="text/css">
	
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@700&family=Poppins:wght@400;500;600&display=swap');
body
{
	background-image: url('../login/logos/asd.svg');
	background-repeat: no-repeat;
	background-attachment: fixed;  
	background-size: cover;
	font-family: "raleway";

}
.center {
	position:absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%,-50%);

}
.popup {
	width: 450px;
	height: 390px;
	padding: 30px 20px;
	background: #FFF;
	border-radius: 10px;
	box-sizing: border-box;
	z-index: 2;
	text-align: center;
	border: 2px solid #0067A9;
}
.popup .title {
	margin: 5px 0px;
	font-size: 50px;
	font-weight: 600;
	color: #000;
}
.popup .description {
	color: #000;
	font-size: 22px;
	padding: 5px;
}
.popup .dismiss-btn {
	margin-top: 15px;
}
.popup .dismiss-btn button {
	padding: 10px 20px;
	background: #fff;
	color: black;
	border: 2px solid #0067A9;
	font-size: 16px;
	font-weight: 600;
	outline: none;
	border-radius: 10px;
	cursor:pointer;
	transition: all 300ms ease-in-out;
}
.popup .dismiss-btn button:hover {
	color: #FFF;
	background: #0067A9;
}
</style>
<body>
<div class="popup center">
	<div class="icon">
		<img src="../login/logos/logo.png" alt="ourpathway" height="100px;">

	</div>
	<div class="title">Successful!</div>
	<div class="description">
		Your details has been successfully submitted. Kindly wait for our verification. <br>Thank you!
	</div>
	<br>
	 <div class="dismiss-btn">
	 	<form action="https://nclexamplifiedreviewcenter.com/quiz">
	 	<button id="dismiss-popup-btn" type="submit">
	 		Dismiss
	 	</button>
	 	</form>
	 </div>
</div>

</body>
</html>