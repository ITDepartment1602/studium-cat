<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, rgba(179, 199, 230, 0.8), rgba(226, 239, 255, 0.8)), url('https://source.unsplash.com/1600x900/?abstract') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            padding: 20px;
            color: #333;
            backdrop-filter: blur(5px);
        }

        nav {
            width: 100%;
            background-color: rgba(7, 110, 163, 0.85);
            padding: 15px 0;
            text-align: center;
            position: absolute;
            top: 0;
            left: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 20px;
            font-size: 16px;
            font-weight: 500;
            text-transform: uppercase;
        }

        nav a:hover {
            color: #ff9e2f;
            transform: scale(1.1);
        }

        h1 {
            font-size: 36px;
            font-weight: 700;
            color: white;
            text-align: center;
            margin-bottom: 50px;
            text-transform: uppercase;
        }

        .package-container {
    display: flex;
    justify-content: center;
    gap: 25px;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding: 30px;
    max-width: 100%;
}


        .package {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 25px;
            text-align: center;
            min-width: 280px;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out, background-color 0.3s ease, border 0.3s ease;
            margin-bottom: 30px;
        }

        .package:hover {
            transform: translateY(-10px);
            background-color: #f9f9f9;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .package-header {
            color: white;
            padding: 35px;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .package-header h2 {
            font-size: 28px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .package-body {
            font-size: 18px;
            color: #555;
        }

        .package-body ul {
            list-style-type: none;
            margin: 20px 0;
            padding: 0;
        }

        .package-body li {
            padding: 8px 0;
            font-weight: 400;
        }



        .buy-btn {
            background-color: #ff9e2f;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .buy-btn:hover {
            background-color: #e68a00;
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        h3 {
            margin-top: 40px;
            font-size: 24px;
            color: #333;
            text-align: center;
        }

        .offer-btn {
            font-size: 18px;
            padding: 15px 40px;
            border-radius: 50px;
            background-color: #ff9e2f;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }

        .offer-btn:hover {
            background-color: #e68a00;
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .package[data-color="#00a6cd"]:hover {
            box-shadow: 0 20px 50px rgba(0, 166, 205, 0.4);
        }

        .package[data-color="#9e1191"]:hover {
            box-shadow: 0 20px 50px rgba(158, 17, 145, 0.4);
        }

        .package[data-color="#eda236"]:hover {
            box-shadow: 0 20px 50px rgba(237, 162, 54, 0.4);
        }

        .package[data-color="#2d9a45"]:hover {
            box-shadow: 0 20px 50px rgba(45, 154, 69, 0.4);
        }

        .package[data-color="#174f37"]:hover {
            box-shadow: 0 20px 50px rgba(23, 79, 55, 0.4);
        }

        .package[data-color="#076ea3"]:hover {
            box-shadow: 0 20px 50px rgba(7, 110, 163, 0.4);
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 30px;
                margin-bottom: 40px;
            }

            .package-container {
                padding: 20px;
            }

            .package-header h2 {
                font-size: 22px;
            }

            .buy-btn {
                font-size: 14px;
                padding: 10px 25px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 24px;
                margin-bottom: 30px;
            }

            .package-container {
                padding: 15px;
            }

            .package-header h2 {
                font-size: 20px;
            }

            .buy-btn {
                font-size: 14px;
                padding: 10px 20px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <a href="#"></a>
        <a href="#"></a>
        <a href="#"></a>
    </nav>



    <div class="package-container">

        <div class="package" data-color="#00a6cd">
            <div class="package-header" style="background-color: #00a6cd;">
                <h2>PACKAGE 1</h2>
            </div>
            <div class="package-body">
                <ul>
                    <li>Unlimited NCLEX Review until you PASS.</li>
                    <li><b>PACKAGE 1</b></li>
                </ul>
            </div>

        </div>

        <div class="package" data-color="#9e1191">
            <div class="package-header" style="background-color: #9e1191;">
                <h2>PACKAGE 2</h2>
            </div>
            <div class="package-body">
                <ul>
                    <li>Unlimited NCLEX Review until you PASS.</li>
                    <li>24/7 dashboard Access</li>
                    <li><b>PACKAGE 2</b></li>
                </ul>
            </div>
        </div>

        <div class="package" data-color="#eda236">
            <div class="package-header" style="background-color: #eda236;">
                <h2>PACKAGE 3</h2>
            </div>
            <div class="package-body">
                <ul>
                    <li>Unlimited NCLEX Review until you PASS.</li>
                    <li>24/7 dashboard Access</li>
                    <li><b>PACKAGE 3</b></li>
                </ul>
            </div>
        </div>

        <div class="package" data-color="#2d9a45">
            <div class="package-header" style="background-color: #2d9a45;">
                <h2>PACKAGE 4</h2>
            </div>
            <div class="package-body">
                <ul>
                    <li>4 months review + Package 1.</li>
                    <li>24/7 dashboard Access</li>
                    <li><b>PACKAGE 4</b></li>
                </ul>
            </div>
        </div>

        <div class="package" data-color="#174f37">
            <div class="package-header" style="background-color: #174f37;">
                <h2>PACKAGE 5</h2>
            </div>
            <div class="package-body">
                <ul>
                    <li>8 months review + Package 1.</li>
                    <li>24/7 dashboard Access</li>
                    <li><b>PACKAGE 5</b></li>
                </ul>
            </div>
        </div>

        <div class="package" data-color="#076ea3">
            <div class="package-header" style="background-color: #076ea3;">
                <h2>PACKAGE 6</h2>
            </div>
            <div class="package-body">
                <ul>
                    <li>1 Year Review.</li>
                    <li>24/7 dashboard Access</li>
                    <li><b>PACKAGE 6</b></li>
                </ul>
            </div>
        </div>
    </div>
    <h3> Buy Now and Get Our Special Offer!</h3>


    <button class="offer-btn">Claim Your Offer Now</button>
</body>
</html>
