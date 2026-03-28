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
    :root {
        --primary-blue: #0066cc;
        --secondary-blue: #1a8cff;
        --accent-red: #ff3333;
        --white: #ffffff;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, var(--white) 0%, #f0f8ff 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .pricing-container {
        max-width: 100%;
        margin: 2rem auto;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pricing-table {
        flex: 0 0 calc(16.66% - 1rem);
        background: var(--white);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        text-align: center;
        position: relative;
        height: auto;
        min-height: 220px;
    }

    .pricing-table:not(.active) {
        height: 220px;
    }

    .pricing-table.active {
        height: auto;
    }

    .pricing-table:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 102, 204, 0.15);
    }

    .pricing-header {
        position: relative;
        padding-bottom: 1rem;
        margin-bottom: 1rem;
        transition: transform 0.3s ease;
    }

    .pricing-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 2px;
        background: var(--primary-blue);
        transition: width 0.3s ease;
    }

    .pricing-table:hover .pricing-header::after {
        width: 80px;
    }

    .price {
        font-size: 2rem;
        color: var(--primary-blue);
        margin: 0.8rem 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
    }

    .features {
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-20px);
        transition: all 0.3s ease-out;
        margin: 0;
        position: relative;
    }

    .pricing-table.active .features {
        max-height: 800px;
        opacity: 1;
        transform: translateY(0);
        margin: 1rem 0;
        transition: all 0.4s ease-in;
    }

    .d {
        padding: 0.4rem;
        margin: 0.4rem 0;
        font-size: 0.85rem;
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.3s ease;
    }

    .pricing-table.active .d {
        opacity: 1;
        transform: translateY(0);
    }

    .pricing-table.active .d:nth-child(1) { transition-delay: 0.1s; }
    .pricing-table.active .d:nth-child(2) { transition-delay: 0.15s; }
    .pricing-table.active .d:nth-child(3) { transition-delay: 0.2s; }
    .pricing-table.active .d:nth-child(4) { transition-delay: 0.25s; }
    .pricing-table.active .d:nth-child(5) { transition-delay: 0.3s; }
    .pricing-table.active .d:nth-child(6) { transition-delay: 0.35s; }
    .pricing-table.active .d:nth-child(7) { transition-delay: 0.4s; }
    .pricing-table.active .d:nth-child(8) { transition-delay: 0.45s; }
    .pricing-table.active .d:nth-child(9) { transition-delay: 0.5s; }
    .pricing-table.active .d:nth-child(10) { transition-delay: 0.55s; }

    .btn {
        opacity: 0;
        transform: translateY(-10px);
        pointer-events: none;
        transition: all 0.3s ease;
        background: linear-gradient(45deg, var(--primary-blue), var(--secondary-blue));
        color: var(--white);
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 500;
        display: inline-block;
        margin-top: 1rem;
    }

    .pricing-table.active .btn {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
        transition-delay: 0.6s;
    }

    .toggle-features {
        background: none;
        border: none;
        color: var(--primary-blue);
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.5rem;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
        width: 100%;
    }

    .toggle-features i {
        transition: transform 0.4s ease;
        display: inline-block;
    }

    .pricing-table.active .toggle-features i {
        transform: rotate(180deg);
    }

    .pricing-table.active .pricing-header {
        transform: translateY(-5px);
    }

    .pricing-header h2 {
        font-size: 1.5rem;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--primary-blue);
        margin-bottom: 0.5rem;
        transition: color 0.3s ease;
    }

    .pricing-header h2:hover {
        color: var(--accent-red);
    }

    @media screen and (max-width: 1200px) {
        .pricing-table {
            flex: 0 0 calc(33.33% - 1rem);
        }
    }

    @media screen and (max-width: 768px) {
        .pricing-table {
            flex: 0 0 calc(50% - 1rem);
        }
    }

    @media screen and (max-width: 480px) {
        .pricing-table {
            flex: 0 0 100%;
        }
    }

    /* Navbar Styles */
    .navbar {
        background: var(--white);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo-container {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .logo-container img {
        height: 100px;
        width: auto;
        max-width: 100%;
    }

    @media (max-width: 768px) {
        .logo-container img {
            height: 80px;
        }
    }

    @media (max-width: 480px) {
        .logo-container img {
            height: 60px;
        }
    }

    .brand-name {
        color: var(--primary-blue);
        font-size: 1.5rem;
        font-weight: bold;
        text-decoration: none;
    }

    .nav-links {
        display: flex;
        gap: 2rem;
        align-items: center;
    }

    .nav-link {
        color: #333;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        padding: 30px 3px;
        font-size: 0.9rem;
        
    }

    .nav-link:hover {
        color: var(--primary-blue);
    }

    .nav-link.home-btn {
        color: var(--black);
        
    }


    @media screen and (max-width: 768px) {
        .navbar {
            padding: 1rem;
        }
        .nav-links {
            gap: 1rem;
        }
        .brand-name {
            font-size: 1.2rem;
        }
        .nav-link {
            font-size: 1.1rem;
            padding: 8px 10px;
        }
    }

    @media screen and (max-width: 480px) {
        .navbar {
            padding: 0.5rem;
        }
        .nav-links {
            gap: 0.5rem;
        }
        .brand-name {
            font-size: 1rem;
        }
        .nav-link {
            font-size: 1rem;
            padding: 5px 8px;
        }
    }

    /* Adjust main content for fixed navbar */
    .main-content {
        margin-top: 80px;
        padding: 1rem;
    }

    /* Footer Styles */
    .footer {
        margin-top: auto;
        background: var(--primary-blue);
        color: var(--white);
        padding: 2rem 0;
        text-align: center;
    }
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 10px;
    }
    .footer-links {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin: 1rem 0;
    }
    .footer-links a {
        color: var(--white);
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .footer-links a:hover {
        color: var(--accent-red);
    }
    .social-links {
        margin: 1rem 0;
    }
    .social-links a {
        color: var(--white);
        font-size: 1.5rem;
        margin: 0 10px;
        transition: all 0.3s ease;
    }
    .social-links a:hover {
        color: var(--accent-red);
        transform: translateY(-3px);
    }

    /* PDF Reviewer Styles */
    .pdfreview {
        position: fixed;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        background-color: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        margin: 1.5rem 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: none;
        opacity: 0;
        transition: opacity 0.5s ease, transform 0.5s ease;
    }

    .pdfreview.show {
        display: block;
        opacity: 1;
        transform: translate(-50%, 0);
    }

    .pdfreview h3 {
        font-size: 1.8rem;
        font-weight: bold;
        color: var(--primary-blue);
        margin-bottom: 1rem;
        text-align: center;
    }

    .pdfreview ul {
        list-style-type: none;
        padding-left: 0;
    }

    .pdfreview li {
        margin: 0.5rem 0;
        padding: 0.5rem;
        background-color: #e0f7fa;
        border-radius: 5px;
        position: relative;
        transition: background-color 0.3s;
        display: flex;
        align-items: center;
    }

    .pdfreview li:before {
        content: '\2022'; /* Unicode for bullet */
        color: var(--primary-blue); /* Color for the bullet */
        font-size: 1.5rem;
        margin-right: 10px;
    }

    .pdfreview li:hover {
        background-color: #b2ebf2;
    }

    @media (max-width: 768px) {
        .pdfreview h3 {
            font-size: 1.5rem;
        }
        .pdfreview p {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        .pdfreview {
            padding: 1rem;
        }
        .pdfreview h3 {
            font-size: 1.2rem;
        }
        .pdfreview p {
            font-size: 0.8rem;
        }
    }

    #close-pdf-reviewer {
        background-color: red;
        color: white;
        border: none;
        border-radius: 30%;
        cursor: pointer;
        font-size: 1.5rem;
        padding: 0.2rem 0.75rem;
        transition: background-color 0.4s;
    }

    #close-pdf-reviewer:hover {
        background-color: darkred;
    }
</style>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Add JavaScript for dropdowns -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.pricing-table');
    
    cards.forEach(card => {
        const toggleBtn = card.querySelector('.toggle-features');
        const features = card.querySelector('.features');
        
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (card.classList.contains('active')) {
                // Close this card
                card.classList.remove('active');
                toggleBtn.querySelector('i').style.transform = 'rotate(0deg)';
            } else {
                // Close all other cards
                cards.forEach(otherCard => {
                    if (otherCard !== card) {
                        otherCard.classList.remove('active');
                        otherCard.querySelector('.toggle-features i').style.transform = 'rotate(0deg)';
                    }
                });
                
                // Open this card
                card.classList.add('active');
                toggleBtn.querySelector('i').style.transform = 'rotate(180deg)';
            }
        });
    });
    
    const pdfReviewerToggle = document.getElementById('pdf-reviewer-toggle');
    const pdfReviewer = document.querySelector('.pdfreview');
    const closePdfReviewer = document.getElementById('close-pdf-reviewer');
    
    pdfReviewerToggle.addEventListener('click', function() {
        pdfReviewer.classList.add('show');
    });
    
    closePdfReviewer.addEventListener('click', function() {
        pdfReviewer.classList.remove('show');
    });
});
</script>

<title>Pricing Table</title>
</head>
<body>
<nav class="navbar">
    <div class="logo-container">
        <img src="nclex.png" alt="NCLEX Logo">
    </div>
    <div class="nav-links">
        <a href="quiz.php" class="nav-link home-btn">
             Home
        </a>
        <a href="#" class="nav-link" id="pdf-reviewer-toggle">PDF Reviewer</a>
        <a href="#" class="nav-link">Contact</a>
    </div>
</nav>
<div class="main-content">
<div class="pricing-container">
<div class="pricing-table">
<div class="pricing-header">
<h2 style="text-align: center;">Package 1</h2>
<div class="price" style="display: none;">
<span class="currency">₱</span>
<span class="amount">499</span>
</div>
<button class="toggle-features">
<i class="fas fa-chevron-down"></i>
</button>
</div>
<div class="features">
<div class="d">Unlimited NCLEX Review until you PASS</div>
<div class="d">24/7 dashboard Access</div>
<div class="d">Lecture: 8am to 12:30 <b>(M-F)</b></div>
<div class="d">Weekly Recap 8am-12nn</div>
<div class="d">Free Study Plan</div>
<div class="d">NCLEX Codex (NARC)</div>
<div class="d">BOOK 2</div>
<div class="d">FREE PDF REVIEWER</div>
<div class="d">Mock Exam-200 Questionnaires</div>
<div class="d">Live Testimony</div>
<div class="d">Passer Certificate</div>
</div>
<a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="btn">ENROLL NOW</a>
</div>

<div class="pricing-table">
<div class="pricing-header">
<h2 style="text-align: center;">Package 2</h2>
<div class="price" style="display: none;">
<span class="currency">₱</span>
<span class="amount">999</span>
</div>
<button class="toggle-features">
<i class="fas fa-chevron-down"></i>
</button>
</div>
<div class="features">
<div class="d">Unlimited NCLEX Review until you PASS</div>
<div class="d">24/7 dashboard Access</div>
<div class="d">Lecture: 8am to 12:30 <b>(M-F)</b></div>
<div class="d">Weekly Recap 8am-12nn</div>
<div class="d">Free Study Plan</div>
<div class="d">NCLEX Codex (NARC)</div>
<div class="d">BOOK 2</div>
<div class="d">FREE PDF REVIEWER</div>
<div class="d">Passer Certificate</div>
<div class="d">Free Edition 1</div>
<div class="d">Free initial Assessment (OP)</div>
</div>
<a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="btn">ENROLL NOW</a>
</div>

<div class="pricing-table">
<div class="pricing-header">
<h2 style="text-align: center;">Package 3</h2>
<div class="price" style="display: none;">
<span class="currency">₱</span>
<span class="amount">1499</span>
</div>
<button class="toggle-features">
<i class="fas fa-chevron-down"></i>
</button>
</div>
<div class="features">
<div class="d">Unlimited NCLEX Review until you PASS</div>
<div class="d">24/7 dashboard Access</div>
<div class="d">Lecture: 8am to 12:30 <b>(M-F)</b></div>
<div class="d">Weekly Recap 8am-12nn</div>
<div class="d">Free Study Plan</div>
<div class="d">NCLEX Codex (NARC)</div>
<div class="d">BOOK 2</div>
<div class="d">FREE PDF REVIEWER</div>
<div class="d">Edition 1(3,300Q) Edition 2(3,000Q)</div>
<div class="d">Mock Exam-200 Questionnaires</div>
<div class="d">Uworld/Archer - 1500Q</div>
</div>
<a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="btn">ENROLL NOW</a>
</div>

<div class="pricing-table">
<div class="pricing-header">
<h2 style="text-align: center;">Package 4</h2>
<div class="price" style="display: none;">
<span class="currency">₱</span>
<span class="amount">2499</span>
</div>
<button class="toggle-features">
<i class="fas fa-chevron-down"></i>
</button>
</div>
<div class="features">
<div class="d">Unlimited NCLEX Review until you PASS</div>
<div class="d">24/7 dashboard Access</div>
<div class="d">Lecture: 8am to 12:30 <b>(M-F)</b></div>
<div class="d">Weekly Recap 8am-12nn</div>
<div class="d">Free Study Plan</div>
<div class="d">NCLEX Codex (NARC)</div>
<div class="d">Optional: PDF REVIEWER</div>
<div class="d">BOOK 2</div>
<div class="d">FREE PDF REVIEWER</div>
<div class="d">Uworld/Archer - 1500Q</div>
</div>
<a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="btn">ENROLL NOW</a>
</div>

<div class="pricing-table">
<div class="pricing-header">
<h2 style="text-align: center;">Package 5</h2>
<div class="price" style="display: none;">
<span class="currency">₱</span>
<span class="amount">3999</span>
</div>
<button class="toggle-features">
<i class="fas fa-chevron-down"></i>
</button>
</div>
<div class="features">
<div class="d">Unlimited NCLEX Review until you PASS</div>
<div class="d">24/7 dashboard Access</div>
<div class="d">Lecture: 8am to 12:30 <b>(M-F)</b></div>
<div class="d">Weekly Recap 8am-12nn</div>
<div class="d">Free Study Plan</div>
<div class="d">NCLEX Codex (NARC)</div>
<div class="d">Optional: PDF REVIEWER</div>
<div class="d">BOOK 2</div>
</div>
<a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="btn">ENROLL NOW</a>
</div>

<div class="pricing-table">
<div class="pricing-header">
<h2 style="text-align: center;">Package 6</h2>
<div class="price" style="display: none;">
<span class="currency">₱</span>
<span class="amount">5999</span>
</div>
<button class="toggle-features">
<i class="fas fa-chevron-down"></i>
</button>
</div>
<div class="features">
<div class="d">Unlimited NCLEX Review until you PASS</div>
<div class="d">24/7 dashboard Access</div>
<div class="d">Lecture: 8am to 12:30 <b>(M-F)</b></div>
<div class="d">Weekly Recap 8am-12nn</div>
<div class="d">Free Study Plan</div>
<div class="d">NCLEX Codex (NARC)</div>
<div class="d">BOOK 2</div>
<div class="d">Optional: PDF REVIEWER</div>
<div class="d">Edition 1(3,300Q)/Edition 2(3,000Q)</div>
<div class="d">Mock Exam-200 Questionnaires</div>
<div class="d">Uworld/Archer - 1500Q</div>
</div>
<a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="btn">ENROLL NOW</a>
</div>
</div>
</div>
<div class="pdfreview">
    <button id="close-pdf-reviewer" style="float: right;">&times;</button>
    <h3 style="text-align: center;"> PDF Reviewer</h3>
    
    <ul>
        <li>Edition 1: ₱2999</li>
        <li>Edition 2: ₱2499</li>
    </ul>
    <p>
        <b>Included in the PDF reviewer are:</b>
        <ul>
            <li>Lippincott 14th Edition</li>
            <li>Kaplan 12th Edition</li>
            <li>Remar Nursing Pharmacology 7th Edition</li>
            <li>Nursing Pharmacology 7th Edition</li>
            <li>Pharmacology Made Easy</li>
            <li>Pharmacology for Nurses</li>
            <li>Pathophysiology for Nurses</li>
        </ul>
    </p>
</div>
<footer class="footer">
    <div class="footer-content">
        <div class="footer-links">
            <a href="#">About Us</a>
            <a href="#">Contact</a>
            <a href="#">Terms of Service</a>
            <a href="#">Privacy Policy</a>
        </div>
        <div class="social-links">
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin"></i></a>
        </div>
        <p>&copy; 2025 NCLEX Amplified. All rights reserved.</p>
    </div>
</footer>
</body>
</html>