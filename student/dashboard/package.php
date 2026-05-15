<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
$fetch  = mysqli_fetch_assoc($select);

date_default_timezone_set('Asia/Manila');
$daysLeft = floor((strtotime($fetch['dateexpired']) - time()) / 86400);
if ($daysLeft < 0) { header('Location: ../../logout.php'); exit; }

$pageTitle = 'Packages — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap" rel="stylesheet">
  <style>
    .pricing { font-family: "Archivo Black", serif; font-weight: 400; font-style: normal; }
    .s-pkg-card {
      background: #fff;
      border-radius: 14px;
      padding: 24px;
      box-shadow: 0 4px 20px rgba(27,73,101,.1);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: box-shadow 0.25s, transform 0.25s;
    }
    .s-pkg-card:hover { box-shadow: 0 10px 30px rgba(27,73,101,.15); transform: translateY(-4px); }
    .s-pkg-pdf-box {
      border: 2px solid var(--s-primary);
      padding: 10px 14px;
      border-radius: 10px;
      margin: 10px 0;
    }
    .s-pkg-enroll {
      display: block;
      background: linear-gradient(135deg, var(--s-primary), var(--s-accent));
      color: #fff;
      font-weight: 600;
      text-align: center;
      padding: 10px;
      border-radius: 8px;
      text-decoration: none;
      margin-top: 16px;
      transition: opacity 0.2s;
    }
    .s-pkg-enroll:hover { opacity: 0.88; color: #fff; }
    .s-pkg-check { color: var(--s-accent); margin-right: 4px; }
    .s-pkg-feature { font-size: 0.83rem; color: var(--s-text); margin: 4px 0; }
    .s-pkg-title { font-size: 1rem; font-weight: 700; color: var(--s-primary); margin-bottom: 4px; }
    .s-pkg-subtitle { font-size: 0.82rem; color: var(--s-muted); margin-bottom: 6px; }
    .s-pkg-price { font-size: 2rem; font-weight: 700; color: var(--s-primary); padding: 8px 0; }
  </style>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">
  <div class="s-page-header">
    <h1>Packages</h1>
    <p>View available NCLEX Amplified review packages</p>
  </div>

  <div class="row g-4">

    <!-- Package 1 -->
    <div class="col-xl-4 col-md-6 col-12">
      <div class="s-pkg-card">
        <div>
          <div class="s-pkg-title">Package 1</div>
          <div class="s-pkg-subtitle">Unlimited NCLEX Review until you PASS</div>
          <div class="s-pkg-price pricing">₱6,999</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>24/7 dashboard Access</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lecture: 8am to 12:30 <b>(M-F)</b></div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Weekly Recap 8am-12nn</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Free Study Plan Consultation</div>
          <div class="s-pkg-pdf-box">
            <div style="color:var(--s-primary); font-weight:600; font-size:0.82rem; margin-bottom:6px;">Free PDF Reviewers:</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>La Charity</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX Amplified Q/A Compilation Edition 1</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Saunders</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lippincott 14th edition</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Mosby Comprehensive Book</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Notes</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Cram Questionnaires</div>
          </div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>250 Items Exclusive Questionnaires</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Live Testimony</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Passer Certificate</div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="s-pkg-enroll">
          <i class="bi bi-facebook me-1"></i> Enroll Now
        </a>
      </div>
    </div>

    <!-- Package 2 -->
    <div class="col-xl-4 col-md-6 col-12">
      <div class="s-pkg-card" style="border:2px solid var(--s-accent);">
        <div>
          <div class="s-pkg-title">Package 2 <span class="s-badge s-badge-teal" style="font-size:0.65rem; vertical-align:middle;">Popular</span></div>
          <div class="s-pkg-subtitle">Unlimited NCLEX Review until you PASS</div>
          <div class="s-pkg-price pricing">₱8,499</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>24/7 dashboard Access</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lecture: 8am to 12:30 <b>(M-F)</b></div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Weekly Recap 8am-12nn</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Free Study Plan Consultation</div>
          <div class="s-pkg-pdf-box">
            <div style="color:var(--s-primary); font-weight:600; font-size:0.82rem; margin-bottom:6px;">Free PDF Reviewers:</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>La Charity</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX Amplified Q/A Compilation Edition 1</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Saunders</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lippincott 14th edition</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Mosby Comprehensive Book</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Notes</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Cram Questionnaires</div>
          </div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Our Very Own NCLEX Codex (PDF)</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX AMPLIFIED Q/A EDITION 2</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>250 Items Exclusive Questionnaires</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Live Testimony</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Passer Certificate</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Mind Conditioning Consultation</div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="s-pkg-enroll">
          <i class="bi bi-facebook me-1"></i> Enroll Now
        </a>
      </div>
    </div>

    <!-- Package 3 -->
    <div class="col-xl-4 col-md-6 col-12">
      <div class="s-pkg-card">
        <div>
          <div class="s-pkg-title">Package 3</div>
          <div class="s-pkg-subtitle">Unlimited NCLEX Review until you PASS</div>
          <div class="s-pkg-price pricing">₱9,999</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>24/7 dashboard Access</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lecture: 8am to 12:30 <b>(M-F)</b></div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Weekly Recap 8am-12nn</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Free Study Plan Consultation</div>
          <div class="s-pkg-pdf-box">
            <div style="color:var(--s-primary); font-weight:600; font-size:0.82rem; margin-bottom:6px;">Free PDF Reviewers:</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>La Charity</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX Amplified Q/A Compilation Edition 1</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Saunders</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lippincott 14th edition</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Mosby Comprehensive Book</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Notes</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Cram Questionnaires</div>
          </div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Our Very Own NCLEX Codex (PDF)</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX AMPLIFIED Q/A EDITION 2</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>250 Items Exclusive Questionnaires</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Live Testimony</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Passer Certificate</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Mind Conditioning Consultation</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Our Pathway Processing Center 5%-10% Discount</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Free Initial Assessment from Our Pathway Processing Center</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>3 months Studium CAT</div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="s-pkg-enroll">
          <i class="bi bi-facebook me-1"></i> Enroll Now
        </a>
      </div>
    </div>

    <!-- Package 4 -->
    <div class="col-xl-4 col-md-6 col-12">
      <div class="s-pkg-card">
        <div>
          <div class="s-pkg-title">Package 4</div>
          <div class="s-pkg-subtitle">4 months Review + Package 1</div>
          <div class="s-pkg-price pricing">₱2,499</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>24/7 dashboard Access</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lecture: 8am to 12:30 <b>(M-F)</b></div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Weekly Recap 8am-12nn</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Free Study Plan Consultation</div>
          <div class="s-pkg-pdf-box">
            <div style="color:var(--s-primary); font-weight:600; font-size:0.82rem; margin-bottom:6px;">Free PDF Reviewers:</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>La Charity</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX Amplified Q/A Compilation Edition 1</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Saunders</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lippincott 14th edition</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Mosby Comprehensive Book</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Notes</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Cram Questionnaires</div>
          </div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>250 Items Exclusive Questionnaires</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Live Testimony</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Passer Certificate</div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="s-pkg-enroll">
          <i class="bi bi-facebook me-1"></i> Enroll Now
        </a>
      </div>
    </div>

    <!-- Package 5 -->
    <div class="col-xl-4 col-md-6 col-12">
      <div class="s-pkg-card">
        <div>
          <div class="s-pkg-title">Package 5</div>
          <div class="s-pkg-subtitle">8 months Review + Package 1</div>
          <div class="s-pkg-price pricing">₱3,499</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>24/7 dashboard Access</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lecture: 8am to 12:30 <b>(M-F)</b></div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Weekly Recap 8am-12nn</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Free Study Plan Consultation</div>
          <div class="s-pkg-pdf-box">
            <div style="color:var(--s-primary); font-weight:600; font-size:0.82rem; margin-bottom:6px;">Free PDF Reviewers:</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>La Charity</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX Amplified Q/A Compilation Edition 1</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Saunders</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lippincott 14th edition</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Mosby Comprehensive Book</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Notes</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Cram Questionnaires</div>
          </div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>250 Items Exclusive Questionnaires</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Live Testimony</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Passer Certificate</div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="s-pkg-enroll">
          <i class="bi bi-facebook me-1"></i> Enroll Now
        </a>
      </div>
    </div>

    <!-- Package 6 -->
    <div class="col-xl-4 col-md-6 col-12">
      <div class="s-pkg-card">
        <div>
          <div class="s-pkg-title">Package 6</div>
          <div class="s-pkg-subtitle">1 year Review + Package 1</div>
          <div class="s-pkg-price pricing">₱4,499</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>24/7 dashboard Access</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lecture: 8am to 12:30 <b>(M-F)</b></div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Weekly Recap 8am-12nn</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Free Study Plan Consultation</div>
          <div class="s-pkg-pdf-box">
            <div style="color:var(--s-primary); font-weight:600; font-size:0.82rem; margin-bottom:6px;">Free PDF Reviewers:</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>La Charity</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX Amplified Q/A Compilation Edition 1</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Saunders</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Lippincott 14th edition</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Mosby Comprehensive Book</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Notes</div>
            <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>NCLEX RN Cram Questionnaires</div>
          </div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>250 Items Exclusive Questionnaires</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Live Testimony</div>
          <div class="s-pkg-feature"><i class="bi bi-check-square-fill s-pkg-check"></i>Passer Certificate</div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank" class="s-pkg-enroll">
          <i class="bi bi-facebook me-1"></i> Enroll Now
        </a>
      </div>
    </div>

  </div><!-- end row -->

  <!-- PDF Reviewer Info -->
  <div class="s-card mt-2">
    <div class="d-flex align-items-start gap-3">
      <i class="bi bi-file-earmark-pdf-fill" style="font-size:1.3rem; color:var(--s-accent); flex-shrink:0; margin-top:2px;"></i>
      <div>
        <div class="fw-semibold mb-1" style="color:var(--s-primary); font-size:0.9rem;">PDF Reviewer Add-On</div>
        <div style="font-size:0.82rem; color:var(--s-muted);">
          <strong>Edition 1: ₱2,999</strong> &nbsp;|&nbsp; <strong>Edition 2: ₱2,499</strong><br>
          Includes: Lippincott 14th Ed · Kaplan 12th Ed · Remar Nursing Pharmacology 7th Ed · Nursing Pharmacology 7th Ed · Pharmacology Made Easy · Pharmacology for Nurses · Pathophysiology for Nurses
        </div>
      </div>
      <button id="pdfReviewerBtn" class="s-btn s-btn-outline ms-auto" style="white-space:nowrap; flex-shrink:0;">
        <i class="bi bi-info-circle me-1"></i> Details
      </button>
    </div>
  </div>

</main>

<div class="s-footer"><span>© Studium 2025, All Right Reserved.</span></div>

<script src="../ty/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('pdfReviewerBtn').addEventListener('click', function () {
    Swal.fire({
        title: 'PDF Reviewer',
        html: `
<strong>Edition 1: ₱2,999</strong><br>
<strong>Edition 2: ₱2,499</strong><br><br>
<b>Included in the PDF reviewer:</b>
<ul style="text-align:left; margin-top:8px;">
    <li>Lippincott 14th Edition</li>
    <li>Kaplan 12th Edition</li>
    <li>Remar Nursing Pharmacology 7th Edition</li>
    <li>Nursing Pharmacology 7th Edition</li>
    <li>Pharmacology Made Easy</li>
    <li>Pharmacology for Nurses</li>
    <li>Pathophysiology for Nurses</li>
</ul>`,
        showCloseButton: true,
        focusConfirm: false,
        confirmButtonText: 'Close',
    });
});
</script>
</body>
</html>
