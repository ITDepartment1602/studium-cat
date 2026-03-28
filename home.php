<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studium CAT</title>
    <link rel="shortcut icon" type="text/css" href="img/logo1.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Text:ital@0;1&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background-color: #F8F9FA;
            color: black;
            font-family: "Roboto", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        .navbar {
            background-color: transparent;
            z-index: 1;
            position: fixed;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .navbar.scrolled {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .footer {
            background-color: #1B4965;
            color: white;
        }

        .hero {
            background-image: url('img/heroimg.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .fontfont {
            font-family: "DM Serif Text", serif;
        }

        .hero h1 {
            color: white;
            font-size: 3rem;
            margin-bottom: 20px;
            font-family: "DM Serif Text", serif;
        }

        .hero p {
            font-size: 1.5rem;
            color: white;
            margin-bottom: 30px;
            font-family: "DM Serif Text", serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #1B4965;

        }

        .card-text {
            color: #6c757d;
            /* Bootstrap's default text-muted color */
            font-size: 1rem;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light">

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand linkx" href="#"><img src="img/logo3.png" style="width: 80px" alt=""></a>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link linkx" href="#features" style="color: white;">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link linkx" href="#pricing" style="color: white;">Pricing</a>
                </li>

                <li class="nav-item">
                    <a class="btn btn-dark linkx" style="background-color: #1B4965;" href="index.php">Login</a>
                </li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>Welcome to Studium CAT</h1>
        <p id="typing-effect"></p>
        <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
        <script>
            var options = {
                strings: [
                    "Simulate Real Exam Conditions",
                    "Focus on Your Test-Taking Strategy",
                    "Improved Accuracy in Measuring Competence",
                    "Real-Time Feedback",
                    "Increased Test Efficiency",
                    "Improved Test-Taking Experience"
                ],
                typeSpeed: 50,
                backSpeed: 25,
                loop: true,
                showCursor: false
            };

            var typed = new Typed("#typing-effect", options);
        </script>
        <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank" class="btn "
            style="background-color:rgb(76, 148, 167); color: white;">Avail
            Now!</a>
    </header>

    <section id="video" class="my-5 text-center" style="padding: 0px 50px;">

        <div class="row" style="margin-top: 10%;">
            <div class="col-12 col-lg-6 d-flex justify-content-center align-items-center mb-4 mb-lg-0">
                <div class="embed-responsive embed-responsive-16by9">
                    <video class="embed-responsive-item" src="img/promotion.mp4" autoplay muted loop
                        allowfullscreen></video>
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex justify-content-center align-items-center">
                <img src="img/mock.png" alt="" class="img-fluid">
            </div>
        </div>
    </section>

    <section id="features" class="my-5">
        <div class="container">
            <h2 class="text-center mb-4 fontfont">Features</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="material-icons" style="font-size: 40px; color: #1B4965;">assignment</i>
                            <h5 class="card-title">Simulate Real Exam Conditions</h5>
                            <p class="card-text">Experience a realistic test environment to better prepare for actual
                                exams.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="material-icons" style="font-size: 40px; color: #1B4965;">track_changes</i>
                            <h5 class="card-title">Focus on Your Test-Taking Strategy</h5>
                            <p class="card-text">Enhance your approach to answering questions effectively.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="material-icons" style="font-size: 40px; color: #1B4965;">assessment</i>
                            <h5 class="card-title">Improved Accuracy in Measuring Competence</h5>
                            <p class="card-text">Get precise evaluations of your knowledge and skills.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="material-icons" style="font-size: 40px; color: #1B4965;">feedback</i>
                            <h5 class="card-title">Real-Time Feedback</h5>
                            <p class="card-text">Receive instant insights to track and improve your performance.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="material-icons" style="font-size: 40px; color: #1B4965;">speed</i>
                            <h5 class="card-title">Increased Test Efficiency</h5>
                            <p class="card-text">Maximize your preparation time with adaptive testing.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="material-icons" style="font-size: 40px; color: #1B4965;">star_rate</i>
                            <h5 class="card-title">Improved Test-Taking Experience</h5>
                            <p class="card-text">Enjoy a seamless and engaging testing process.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="my-5 bg-light">
        <div class="container text-center">
            <h2 class="fontfont mb-4">Pricing Plans</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body">
                            <h3 class="card-title">1 Month</h3>
                            <p class="card-text">2,999.00 Php</p>
                            <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank"
                                class="btn btn-primary">Avail Now!</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body">
                            <h3 class="card-title">3 Months</h3>
                            <p class="card-text">3,499.00 Php</p>
                            <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank"
                                class="btn btn-primary">Avail Now!</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body">
                            <h3 class="card-title">6 Months</h3>
                            <p class="card-text">4,499.00 Php</p>
                            <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank"
                                class="btn btn-primary">Avail Now!</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-body">
                            <h3 class="card-title">12 Months</h3>
                            <p class="card-text">5,999.00 Php</p>
                            <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank"
                                class="btn btn-primary">Avail Now!</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer text-center py-4">
        <p>&copy; 2025 Studium. All rights reserved.</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Change navbar background on scroll
        window.addEventListener('scroll', function () {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>

</html>