<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
    <link rel="stylesheet" href="student/dashboard/css/footer.css">

    <title>NCLEX Amplified</title>
    <link rel="shortcut icon" type="text/css" href="img/logo.png">
</head>

<body>
    <header>
    <nav>
        <ul class='nav-bar'>
            <li class='logo'><a href='https://www.facebook.com/NCLEX.Amplified.Official' target="_blank"><img src='img/logo 2.png'></a></li>
            <span class="menua">
                <a href="index.php"><i class="fa fa-sign-in" aria-hidden="true"></i> <b>&nbsp;Login &nbsp;</b></a>
                &nbsp;
                <a href=""><i class="fa fa-sign-in" aria-hidden="true"></i> <b>&nbsp;Sign up &nbsp;</b></a>
            </span>
            <input type='checkbox' id='check' />
            
            <span class="menu">
                <li><a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank"><i class="fa fa-shopping-cart" style="font-size:25px" aria-hidden="true"></i> <b></b></a></li>
                <li><a href="https://www.facebook.com/NCLEX.Amplified.Technical" target="_blank"><i class="fa fa-phone" style="font-size:25px" aria-hidden="true"></i> <b></b></a></li>
                <label for="check" class="close-menu"><i class="fas fa-times"></i></label>
            </span>
            <label for="check" class="open-menu"><i class="fas fa-bars"></i></label>
        </ul>
    </nav>
    </header>

<br><br><br>

  <div class="wrapper">
    <div class="container main">
        <div class="row" style="height: 60%;">
            <div class="col-md-6 side-image">
                       
                <!-------------      image     ------------->
                
                <img src="login/logos/logo.png" alt="" width="30%" style="margin-left: 35%;margin-top: 10%;">
                <br>
                <center><p style="font-size:30px"><b>Achieve your American Deam!</b></p></center>
                
                <?php include "login/testi/Testimonial.php";?>
                
            </div>

            <div class="col-md-6 right">
                
                <div class="input-box">
                   
                   <header style="font-size: 40px;">Sign up</header>
                   <p style="margin-top: -40px; font-size: 15px; text-align: center;">To enable us to contact you and wait for your access, <br>please provide your details.</p>

                   <form method="POST" action="signup/validation.php" onsubmit="return validation()">
                   <div class="input-field">
                        <input type="email" class="input" required name="email" id="email" onkeypress="clear()" autocomplete="off">
                        <label for="email">Email:</label> 
                    </div>

                    <div class="input-field">
                        <input type="text" class="input" required name="fullname" id="fullname" onkeypress="clear()" autocomplete="off">
                        <label for="fullname">Full Name:</label> 
                    </div>

                    <div class="input-field">
                        <input type="text" class="input" required name="facebookname" id="facebookname" onkeypress="clear()" autocomplete="off">
                        <label for="facebookname">Facebook Name:</label> 
                    </div>

                    <div class="input-field">
                        <input type="text" class="input" required name="contactnumber" id="contactnumber" onkeypress="clear()" autocomplete="off">
                        <label for="contactnumber">Contact Number:</label> 
                    </div>

                    <div class="input-field">
                        <input type="text" class="input" required name="address" id="address" onkeypress="clear()" autocomplete="off">
                        <label for="address">Address:</label> 
                    </div>

                    <div class="input-field">
                        <input type="text" class="input" required name="rcenter" id="rcenter" onkeypress="clear()" autocomplete="off">
                        <label for="rcenter">Review Center:</label> 
                    </div>
                    <input type="checkbox" id="vehicle1" name="agree" value="argee" required> I agree to the <a href="">Terms & Conditions</a> and <a href="">Privacy Policy</a>
                    <br>
                    <br>
                        <button class="submit" type="submit" name="submit" style="width:90px; float: right;"><b>Submit</b></button>

                   </form>
                   </div> 
                </div>  
                <p></p>
            </div>
        </div>
    </div>
</div>
<br><br><br><br>
<?php include "student/dashboard/footer.php";?>
</body>
</html>