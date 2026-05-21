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

// Check discount eligibility
$hasReview = mysqli_num_rows(mysqli_query($con, "SELECT * FROM studentReviews WHERE studentId='$user_id'")) > 0;

// Base prices
$basicPlanPrice      = 2999.00;
$proPlanPrice        = 3499.00;
$enterprisePlanPrice = 4499.00;
$ultimatePlanPrice   = 5999.00;

if ($hasReview) {
    $basicPlanPrice      *= 0.80;
    $proPlanPrice        *= 0.80;
    $enterprisePlanPrice *= 0.80;
    $ultimatePlanPrice   *= 0.80;
}

$pageTitle = 'Subscription Plans — Studium';

$plans = [
    [
        'name'     => 'Basic Plan',
        'duration' => '1 month',
        'price'    => $basicPlanPrice,
        'original' => $hasReview ? $basicPlanPrice / 0.8 : null,
        'icon'     => 'bi-stars',
        'color'    => 'var(--s-primary)',
        'popular'  => false,
    ],
    [
        'name'     => 'Pro Plan',
        'duration' => '3 months',
        'price'    => $proPlanPrice,
        'original' => $hasReview ? $proPlanPrice / 0.8 : null,
        'icon'     => 'bi-gem',
        'color'    => 'var(--s-accent)',
        'popular'  => true,
    ],
    [
        'name'     => 'Enterprise Plan',
        'duration' => '6 months',
        'price'    => $enterprisePlanPrice,
        'original' => $hasReview ? $enterprisePlanPrice / 0.8 : null,
        'icon'     => 'bi-rocket-takeoff',
        'color'    => '#6366f1',
        'popular'  => false,
    ],
    [
        'name'     => 'Ultimate Plan',
        'duration' => '12 months',
        'price'    => $ultimatePlanPrice,
        'original' => $hasReview ? $ultimatePlanPrice / 0.8 : null,
        'icon'     => 'bi-trophy-fill',
        'color'    => '#f59e0b',
        'popular'  => false,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
  <style>
    .s-plan-card {
      background: var(--s-card-bg);
      border: 2px solid var(--s-border);
      border-radius: 16px;
      padding: 28px 24px;
      text-align: center;
      transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
      position: relative;
      display: flex;
      flex-direction: column;
    }
    .s-plan-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(0,0,0,.1);
    }
    .s-plan-card.popular {
      border-color: var(--s-accent);
      box-shadow: 0 8px 30px rgba(13,148,136,.18);
    }
    .s-plan-popular-badge {
      position: absolute;
      top: -14px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--s-accent);
      color: #fff;
      font-size: 0.72rem;
      font-weight: 600;
      padding: 4px 16px;
      border-radius: 20px;
      white-space: nowrap;
    }
    .s-plan-icon {
      font-size: 2rem;
      margin-bottom: 12px;
    }
    .s-plan-price {
      font-size: 2.2rem;
      font-weight: 700;
      color: var(--s-primary);
      line-height: 1.1;
    }
    .s-plan-original {
      font-size: 1rem;
      color: var(--s-muted);
      text-decoration: line-through;
      margin-bottom: 4px;
    }
    .s-plan-duration {
      font-size: 0.78rem;
      color: var(--s-muted);
      margin-bottom: 16px;
    }
    .s-plan-enroll {
      display: block;
      padding: 10px 24px;
      border-radius: 30px;
      background: linear-gradient(135deg, var(--s-primary), var(--s-accent));
      color: #fff;
      font-weight: 600;
      font-size: 0.9rem;
      text-decoration: none;
      text-align: center;
      margin-top: auto;
      transition: opacity 0.2s, transform 0.2s;
    }
    .s-plan-enroll:hover { opacity: 0.9; transform: scale(1.02); color: #fff; }
    .s-plan-features {
      list-style: none; padding: 0; margin: 12px 0 20px;
      text-align: left; font-size: 0.82rem; color: var(--s-muted);
    }
    .s-plan-features li {
      display: flex; align-items: center; gap: 8px;
      padding: 5px 0; border-bottom: 1px solid var(--s-border);
    }
    .s-plan-features li:last-child { border-bottom: none; }
    .s-plan-features li i { color: var(--s-accent); flex-shrink: 0; font-size: 0.85rem; }
  </style>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">
  <div class="s-page-header">
    <h1>Subscription Plans</h1>
    <p>Choose the plan that fits your study schedule and goals</p>
  </div>

  <?php if ($hasReview): ?>
  <div class="s-alert-banner mb-4" style="background:rgba(13,148,136,.08); border-color:var(--s-accent); color:var(--s-accent)">
    <i class="bi bi-tag-fill me-2"></i> <strong>Exclusive 20% Discount Applied!</strong> As a returning student with a review record, you qualify for discounted rates.
  </div>
  <?php endif; ?>

  <!-- Pricing Grid -->
  <div class="row g-4 mb-4">
    <?php foreach ($plans as $plan): ?>
    <div class="col-xl-3 col-md-6 col-12">
      <div class="s-plan-card <?php echo $plan['popular'] ? 'popular' : ''; ?>">
        <?php if ($plan['popular']): ?>
        <div class="s-plan-popular-badge"><i class="bi bi-star-fill me-1"></i> Most Popular</div>
        <?php endif; ?>

        <div class="s-plan-icon" style="color:<?php echo $plan['color']; ?>;">
          <i class="bi <?php echo $plan['icon']; ?>"></i>
        </div>

        <h4 class="fw-bold mb-1" style="color:var(--s-primary); font-size:1.1rem;"><?php echo $plan['name']; ?></h4>

        <?php if ($plan['original']): ?>
        <div class="s-plan-original">₱<?php echo number_format($plan['original'], 2); ?></div>
        <div class="s-plan-price" style="color:<?php echo $plan['color']; ?>;">
          ₱<?php echo number_format($plan['price'], 2); ?>
        </div>
        <span class="s-badge s-badge-teal mb-2" style="font-size:0.7rem;">20% OFF</span>
        <?php else: ?>
        <div class="s-plan-price" style="color:<?php echo $plan['color']; ?>;">
          ₱<?php echo number_format($plan['price'], 2); ?>
        </div>
        <?php endif; ?>

        <div class="s-plan-duration">/ <?php echo $plan['duration']; ?></div>

        <ul class="s-plan-features">
          <li><i class="bi bi-check-circle-fill"></i> Full QBank Access</li>
          <li><i class="bi bi-check-circle-fill"></i> NGN Practice Questions</li>
          <li><i class="bi bi-check-circle-fill"></i> Performance Analytics</li>
          <li><i class="bi bi-check-circle-fill"></i> Study Notes</li>
        </ul>

        <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter" target="_blank"
           class="s-plan-enroll">
          <i class="bi bi-facebook me-1"></i> Enroll Now
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Info note -->
  <div class="s-card">
    <div class="d-flex align-items-start gap-3">
      <i class="bi bi-info-circle-fill" style="font-size:1.2rem; color:var(--s-accent); flex-shrink:0; margin-top:2px;"></i>
      <div style="font-size:0.82rem; color:var(--s-muted);">
        <strong style="color:var(--s-primary);">How to Enroll</strong><br>
        Click "Enroll Now" to message our Facebook page and complete your enrollment. Our team will process your subscription and activate your account within 24 hours.
        <br><br>
        <strong style="color:var(--s-primary);">Need help?</strong>
        Contact us at <a href="https://www.facebook.com/NCLEXAmplifiedTechSupport/" target="_blank" style="color:var(--s-accent);">@NCLEXAmplifiedTechSupport</a>
      </div>
    </div>
  </div>
</main>


</body>
</html>
