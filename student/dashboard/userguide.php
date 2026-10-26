<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$pageTitle = 'User Guide — Studium';

$guideSections = [
    [
        'id'    => 'cat-getting-started',
        'icon'  => 'bi-rocket-takeoff-fill',
        'title' => 'Getting Started',
        'desc'  => "Learn the basics: sidebar navigation, account info, and logging out.",
        'faqs'  => [
            [
                'id'      => 'faq-gs-nav',
                'q'       => 'How do I move around the dashboard?',
                'steps'   => [
                    "On a computer, the sidebar on the left lists all main sections: Home, View Profile, Exam History, My Notes, Subscription, User Guide, and Contact Us.",
                    "On a phone or tablet, tap the menu icon (<i class=\"bi bi-list\"></i>) in the top-left corner to open the same menu.",
                    "Tap any item to go straight to that page. The page you're currently viewing is highlighted in the menu.",
                    "On mobile, tap outside the menu (or the menu icon again) to close it.",
                ],
                'related' => ['faq-home-overview', 'faq-profile-view'],
            ],
            [
                'id'      => 'faq-gs-account',
                'q'       => 'Where can I see my account expiration date?',
                'steps'   => [
                    "Look at the bottom of the sidebar — your <strong>Expiration Date</strong> and time are always shown there.",
                    "If your account is about to expire (7 days or less), a small bell notification will appear at the top of the page to remind you.",
                    "To extend your access, tap <strong>Upgrade Now</strong> at the bottom of the sidebar — this opens our official Facebook page where our team can assist you.",
                ],
                'related' => ['faq-sub-status', 'faq-support-contact'],
            ],
            [
                'id'      => 'faq-gs-logout',
                'q'       => 'How do I log out of my account?',
                'steps'   => [
                    "Open the sidebar (tap the menu icon on mobile if it's hidden).",
                    "Scroll down to the very bottom of the sidebar.",
                    "Tap <strong>Logout</strong> — you'll be securely signed out and returned to the login page.",
                ],
                'related' => [],
            ],
        ],
    ],
    [
        'id'    => 'cat-home',
        'icon'  => 'bi-house-fill',
        'title' => 'Home Dashboard',
        'desc'  => "Your hero banner, practice modes, statistics, topics, and readiness score.",
        'faqs'  => [
            [
                'id'      => 'faq-home-overview',
                'q'       => 'What will I see when I open my Home dashboard?',
                'steps'   => [
                    "A welcome banner showing your name and a quick overview of your study progress.",
                    "Three <strong>Practice Mode</strong> cards — Traditional QBank, NGN Exam, and CAT Exam — each opens a different way to practice.",
                    "A <strong>Statistics</strong> section showing your overall accuracy and exam activity.",
                    "A <strong>Study Topics / Concepts</strong> section to browse questions by subject.",
                    "An <strong>NCLEX Readiness</strong> card that estimates how prepared you are based on your performance.",
                ],
                'related' => ['faq-trad-start', 'faq-ngn-start', 'faq-cat-start'],
            ],
            [
                'id'      => 'faq-home-stats',
                'q'       => 'How do I read my Statistics section?',
                'steps'   => [
                    "Find the <strong>Statistics</strong> card on your Home dashboard.",
                    "The donut chart shows your overall accuracy — the percentage of questions you've answered correctly so far.",
                    "Below the chart you'll see totals such as questions answered and exams completed.",
                    "These numbers update automatically every time you finish a question or exam.",
                ],
                'related' => ['faq-hist-view'],
            ],
            [
                'id'      => 'faq-home-readiness',
                'q'       => 'What does my NCLEX Readiness score mean?',
                'steps'   => [
                    "Locate the <strong>NCLEX Readiness</strong> card on your Home dashboard.",
                    "This score is calculated from your recent exam performance across all topics.",
                    "<strong>Teal/Green</strong> = Ready (75% and above) &nbsp;•&nbsp; <strong>Amber/Yellow</strong> = Almost There (60–74%) &nbsp;•&nbsp; <strong>Red</strong> = Needs More Work (below 60%).",
                    "Keep practicing topics with lower scores to improve your overall readiness.",
                ],
                'related' => ['faq-hist-badges'],
            ],
            [
                'id'      => 'faq-home-topics',
                'q'       => 'How do I browse Study Topics or Concepts?',
                'steps'   => [
                    "On your Home dashboard, scroll to the <strong>Study Topics</strong> (Concepts) section.",
                    "Each card shows an icon and the subject name (e.g., Pharmacology, Med-Surg, Maternal &amp; Child Health).",
                    "Tap a topic card to see its concepts, then tap <strong>Start</strong> to begin a Traditional practice exam focused on that topic.",
                ],
                'related' => ['faq-trad-start'],
            ],
        ],
    ],
    [
        'id'    => 'cat-traditional',
        'icon'  => 'bi-journal-text',
        'title' => 'Traditional QBank',
        'desc'  => "Pick a topic, answer questions, and review the rationale for each one.",
        'faqs'  => [
            [
                'id'      => 'faq-trad-start',
                'q'       => 'How do I start a Traditional practice exam?',
                'steps'   => [
                    "From the Home dashboard, tap the <strong>Traditional QBank</strong> practice mode card — or pick a subject from <strong>Study Topics</strong>.",
                    "On the Topic Selection page, choose the concept(s) you want to practice. Tap the info icon if you need instructions.",
                    "Tap <strong>Start Exam</strong> to begin.",
                    "Questions appear one at a time. Read carefully, select your answer, then tap <strong>Submit Answer</strong>.",
                ],
                'related' => ['faq-tools-calc', 'faq-hist-rationale'],
            ],
            [
                'id'      => 'faq-trad-answer',
                'q'       => 'How do I answer and submit a question?',
                'steps'   => [
                    "Read the question stem and all answer choices carefully.",
                    "Select your answer by tapping/clicking the option.",
                    "Tap <strong>Submit Answer</strong> at the bottom of the page.",
                    "You'll be taken to the Rationale page, which explains the correct answer.",
                ],
                'related' => ['faq-trad-rationale', 'faq-tools-calc'],
            ],
            [
                'id'      => 'faq-trad-rationale',
                'q'       => 'How do I view the rationale after answering?',
                'steps'   => [
                    "After submitting, you're automatically taken to the <strong>Rationale</strong> page.",
                    "Review the explanation for the correct answer and why the other choices are incorrect.",
                    "A progress drawer on the side shows every question you've answered so far in this exam.",
                    "Tap <strong>Next Question</strong> to continue — or, once you reach the last question, tap <strong>View Results</strong> to see your final score.",
                ],
                'related' => ['faq-hist-rationale', 'faq-trad-results'],
            ],
            [
                'id'      => 'faq-trad-results',
                'q'       => 'How do I view my final results after finishing?',
                'steps'   => [
                    "After answering the last question and tapping <strong>View Results</strong>, you'll land on your Results page.",
                    "Your overall score, accuracy percentage, and a breakdown by topic are displayed.",
                    "This attempt is automatically saved — you can find it later under <strong>Exam History</strong>.",
                ],
                'related' => ['faq-hist-view'],
            ],
        ],
    ],
    [
        'id'    => 'cat-ngn',
        'icon'  => 'bi-diagram-3-fill',
        'title' => 'NGN Exam',
        'desc'  => "Next Generation NCLEX questions with pause/resume and progress tracking.",
        'faqs'  => [
            [
                'id'      => 'faq-ngn-what',
                'q'       => 'What is the NGN Exam mode?',
                'steps'   => [
                    "<strong>NGN</strong> stands for \"Next Generation NCLEX\" — newer question formats designed to test clinical judgment.",
                    "An NGN set mixes several question types: Bowtie, SATA, Matrix Multiple Response (MMR), Matrix Multiple Pair (MPR), Drag &amp; Drop, Dropdown (Cloze), Highlight, and Traditional.",
                    "NGN Exam mode is available to students with a <strong>Package 2</strong> subscription.",
                ],
                'related' => ['faq-cat-what', 'faq-sub-packages'],
            ],
            [
                'id'      => 'faq-ngn-start',
                'q'       => 'How do I start an NGN Exam?',
                'steps'   => [
                    "From the Home dashboard, tap the <strong>NGN Exam</strong> practice mode card.",
                    "A pop-up will appear — review the instructions, then tap <strong>Start</strong> for a new set, or <strong>Resume</strong> if you have one in progress.",
                    "Answer each question based on its type (see \"What question types will I encounter?\" below).",
                    "Your progress is shown at the top using a progress ring and percentage.",
                ],
                'related' => ['faq-ngn-types', 'faq-ngn-pause'],
            ],
            [
                'id'      => 'faq-ngn-pause',
                'q'       => 'Can I pause and resume an NGN Exam?',
                'steps'   => [
                    "Yes — if you need to stop midway, you can simply close the page or navigate away.",
                    "Your progress is saved automatically.",
                    "The next time you tap the <strong>NGN Exam</strong> card from Home, you'll be given the option to <strong>Resume</strong> exactly where you left off.",
                ],
                'related' => ['faq-ngn-start'],
            ],
            [
                'id'      => 'faq-ngn-types',
                'q'       => 'What question types will I encounter in NGN?',
                'steps'   => [
                    "<strong>Highlight</strong> — tap or select the words/phrases in a passage that are most relevant.",
                    "<strong>Bowtie (BTQ)</strong> — fill in the condition, actions, and parameters of a bowtie-shaped diagram.",
                    "<strong>SATA</strong> (Select All That Apply) — choose every option that applies, not just one.",
                    "<strong>MMR / MPR</strong> (Matrix or Pair Response) — choose one or more options per row in a table.",
                    "<strong>Drag &amp; Drop</strong> — arrange items in the correct order by dragging them into place.",
                    "<strong>Dropdown (Cloze)</strong> — pick the correct word or phrase from dropdown menus inside a sentence.",
                    "<strong>Traditional</strong> — standard multiple-choice, same as the Traditional QBank.",
                ],
                'related' => ['faq-tools-calc'],
            ],
            [
                'id'      => 'faq-ngn-progress',
                'q'       => 'How do I track my progress during an NGN Exam?',
                'steps'   => [
                    "At the top of the exam screen, a progress ring shows the percentage of questions completed.",
                    "Use the Question Navigator to jump between questions or check which ones you've already answered.",
                    "The session timer keeps running continuously, even as you switch between questions.",
                ],
                'related' => ['faq-tools-timer'],
            ],
        ],
    ],
    [
        'id'    => 'cat-cat-exam',
        'icon'  => 'bi-cpu-fill',
        'title' => 'CAT Exam',
        'desc'  => "Computer Adaptive Test mode, available for Package 2 students.",
        'faqs'  => [
            [
                'id'      => 'faq-cat-what',
                'q'       => 'What is CAT Exam mode?',
                'steps'   => [
                    "<strong>CAT</strong> stands for \"Computer Adaptive Test\" — it simulates the real NCLEX experience, where question difficulty adjusts based on how you're doing.",
                    "CAT Exam mode is exclusive to <strong>Package 2</strong> subscribers.",
                    "It also unlocks your CAT Exam statistics on the Home dashboard.",
                ],
                'related' => ['faq-sub-packages'],
            ],
            [
                'id'      => 'faq-cat-start',
                'q'       => 'How do I start a CAT Exam?',
                'steps'   => [
                    "From the Home dashboard, tap the <strong>CAT Exam</strong> practice mode card.",
                    "Review the on-screen instructions, then tap <strong>Start Exam</strong>.",
                    "Answer each question — the difficulty of the next question adapts based on whether you answered correctly.",
                    "The exam ends automatically once the system determines a stable result, just like the real NCLEX.",
                ],
                'related' => ['faq-ngn-start'],
            ],
            [
                'id'      => 'faq-cat-access',
                'q'       => 'Who can access CAT Exam mode?',
                'steps'   => [
                    "CAT Exam mode (and its statistics) is only available to students with a <strong>Package 2</strong> subscription.",
                    "If you're on Package 1 and want to unlock CAT Exam mode and the NGN QBank, tap <strong>Upgrade Now</strong> in the sidebar to message our team.",
                ],
                'related' => ['faq-sub-packages', 'faq-support-contact'],
            ],
        ],
    ],
    [
        'id'    => 'cat-tools',
        'icon'  => 'bi-tools',
        'title' => 'Exam Tools',
        'desc'  => "Calculator, quick notes, fullscreen mode, and the session timer.",
        'faqs'  => [
            [
                'id'      => 'faq-tools-calc',
                'q'       => 'How do I use the on-screen calculator?',
                'steps'   => [
                    "While taking any exam, look for the <strong>Calculator</strong> icon in the toolbar.",
                    "Tap it to open the on-screen calculator.",
                    "Use it for dosage calculations or any math needed for the question.",
                    "Tap the icon again (or its close button) to hide it.",
                ],
                'related' => ['faq-trad-answer'],
            ],
            [
                'id'      => 'faq-tools-notes',
                'q'       => 'How do I take notes during an exam?',
                'steps'   => [
                    "While taking any exam, tap the <strong>Notes</strong> icon in the toolbar.",
                    "Type your note in the box that appears — useful for jotting down key facts or things to review later.",
                    "Tap <strong>Save</strong> to store the note.",
                    "All saved notes can be viewed anytime from <strong>My Notes</strong> in the sidebar.",
                ],
                'related' => ['faq-notes-view'],
            ],
            [
                'id'      => 'faq-tools-fullscreen',
                'q'       => 'How do I use fullscreen mode?',
                'steps'   => [
                    "While taking any exam, tap the <strong>Fullscreen</strong> icon in the toolbar.",
                    "This hides browser distractions and gives you more room to focus on the question.",
                    "Tap the icon again (or press Esc) to exit fullscreen.",
                ],
                'related' => [],
            ],
            [
                'id'      => 'faq-tools-timer',
                'q'       => 'How does the exam timer work?',
                'steps'   => [
                    "A timer is shown at the top of the exam screen once you start.",
                    "It counts continuously for your entire session, even as you move between questions.",
                    "There's no per-question time limit — focus on accuracy and pace yourself overall.",
                ],
                'related' => ['faq-ngn-progress'],
            ],
        ],
    ],
    [
        'id'    => 'cat-history',
        'icon'  => 'bi-clock-history',
        'title' => 'Exam History',
        'desc'  => "Review past attempts, filter results, and re-check rationales.",
        'faqs'  => [
            [
                'id'      => 'faq-hist-view',
                'q'       => 'How do I view my past exam attempts?',
                'steps'   => [
                    "Tap <strong>Exam History</strong> in the sidebar.",
                    "You'll see a list of all your completed exams with date, type, and score.",
                    "KPI cards at the top summarize your total exams, average score, and more.",
                ],
                'related' => ['faq-home-stats'],
            ],
            [
                'id'      => 'faq-hist-filter',
                'q'       => 'How do I filter or search my exam history?',
                'steps'   => [
                    "On the Exam History page, use the filter tabs to switch between exam types (Traditional, NGN, CAT).",
                    "Use the search box to find a specific attempt by date or keyword.",
                    "Use the pagination controls at the bottom to browse older results.",
                ],
                'related' => ['faq-hist-view'],
            ],
            [
                'id'      => 'faq-hist-badges',
                'q'       => 'What do the score badge colors mean?',
                'steps'   => [
                    "<strong>Teal/Green</strong> badge = Pass / \"Ready\" — score of 75% or higher.",
                    "<strong>Amber/Yellow</strong> badge = \"Almost There\" — score between 60% and 74%.",
                    "<strong>Red</strong> badge = \"Needs Work\" — score below 60%.",
                    "Use these as a guide to know which topics need more review.",
                ],
                'related' => ['faq-home-readiness'],
            ],
            [
                'id'      => 'faq-hist-rationale',
                'q'       => 'How do I review my answers and rationale from a past exam?',
                'steps'   => [
                    "On the Exam History page, tap any completed attempt.",
                    "Review each question, your selected answer, the correct answer, and the rationale explanation.",
                    "Use this to identify which topics you should practice more.",
                ],
                'related' => ['faq-trad-rationale'],
            ],
        ],
    ],
    [
        'id'    => 'cat-notes',
        'icon'  => 'bi-stickies-fill',
        'title' => 'My Notes',
        'desc'  => "Create, view, and delete your personal study notes.",
        'faqs'  => [
            [
                'id'      => 'faq-notes-add',
                'q'       => 'How do I add a new note?',
                'steps'   => [
                    "Go to <strong>My Notes</strong> in the sidebar.",
                    "Tap the <strong>Add Note</strong> button.",
                    "Type your note's title and content in the form that appears.",
                    "Tap <strong>Save</strong> to add it to your notes list.",
                ],
                'related' => ['faq-tools-notes'],
            ],
            [
                'id'      => 'faq-notes-view',
                'q'       => 'How do I view my saved notes?',
                'steps'   => [
                    "Go to <strong>My Notes</strong> in the sidebar.",
                    "All your saved notes are listed, with the most recent at the top.",
                    "Tap a note to expand and read its full content.",
                ],
                'related' => ['faq-tools-notes'],
            ],
            [
                'id'      => 'faq-notes-delete',
                'q'       => 'How do I delete a note?',
                'steps'   => [
                    "Go to <strong>My Notes</strong> in the sidebar.",
                    "Find the note you want to remove.",
                    "Tap the trash icon next to the note.",
                    "Confirm the deletion in the pop-up — this action cannot be undone.",
                ],
                'related' => ['faq-notes-view'],
            ],
        ],
    ],
    [
        'id'    => 'cat-profile',
        'icon'  => 'bi-person-vcard-fill',
        'title' => 'Profile & Subscription',
        'desc'  => "Manage your account info and check your subscription plan.",
        'faqs'  => [
            [
                'id'      => 'faq-profile-view',
                'q'       => 'How do I view my profile and account information?',
                'steps'   => [
                    "Tap <strong>View Profile</strong> in the sidebar.",
                    "Your profile page shows your name, email, and other account details.",
                    "A subscription status card shows your current plan and expiration date.",
                ],
                'related' => ['faq-gs-account'],
            ],
            [
                'id'      => 'faq-sub-status',
                'q'       => 'How do I check my subscription status?',
                'steps'   => [
                    "Tap <strong>View Profile</strong> or <strong>Subscription</strong> in the sidebar.",
                    "Your current plan name and expiration date are displayed.",
                    "The sidebar footer also always shows your expiration date for quick reference.",
                ],
                'related' => ['faq-sub-packages', 'faq-gs-account'],
            ],
            [
                'id'      => 'faq-sub-packages',
                'q'       => "What's the difference between Package 1 and Package 2?",
                'steps'   => [
                    "<strong>Package 1</strong> includes access to the Traditional QBank only.",
                    "<strong>Package 2</strong> includes everything in Package 1, plus the NGN QBank, CAT Exam mode, and their statistics on your Home dashboard.",
                    "To upgrade, tap <strong>Upgrade Now</strong> in the sidebar or visit the Subscription page to see available plans.",
                ],
                'related' => ['faq-ngn-what', 'faq-cat-access'],
            ],
            [
                'id'      => 'faq-sub-plans',
                'q'       => 'How do I view or change my subscription plan?',
                'steps'   => [
                    "Tap <strong>Subscription</strong> in the sidebar.",
                    "Browse the available plans (Basic, Pro, Enterprise, Ultimate) along with their durations and prices.",
                    "Tap <strong>Enroll Now</strong> on your preferred plan — this messages our official Facebook page so our team can process your upgrade.",
                ],
                'related' => ['faq-support-contact'],
            ],
        ],
    ],
    [
        'id'    => 'cat-support',
        'icon'  => 'bi-life-preserver',
        'title' => 'Help & Support',
        'desc'  => "Contact our team or watch the video walkthrough.",
        'faqs'  => [
            [
                'id'      => 'faq-support-contact',
                'q'       => 'How do I contact support?',
                'steps'   => [
                    "Tap <strong>Contact Us</strong> in the sidebar — this opens our official Facebook page.",
                    "Send us a message describing your concern (account issues, payment, technical problems, etc.).",
                    "Our team typically responds and assists within 24 hours.",
                ],
                'related' => ['faq-sub-plans'],
            ],
            [
                'id'      => 'faq-support-video',
                'q'       => 'Where can I watch the video walkthrough?',
                'steps'   => [
                    "Scroll down to the <strong>Help &amp; Support</strong> section below and tap \"Watch Video Walkthrough\" to open a quick video tour of the dashboard in a new tab.",
                    "This is a great option if you prefer a visual, narrated overview instead of reading.",
                ],
                'related' => [],
            ],
        ],
    ],
];

// Build a lookup so "Related" chips can show the linked question's text
$faqLookup = [];
foreach ($guideSections as $sec) {
    foreach ($sec['faqs'] as $faq) {
        $faqLookup[$faq['id']] = $faq['q'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
  <style>
    /* ============ User Guide Page ============ */
    .ug-hero {
      background: var(--s-card-bg);
      border: 1px solid var(--s-border);
      border-radius: var(--s-radius-lg);
      padding: 48px 28px;
      text-align: center;
      margin-bottom: 32px;
    }
    .ug-hero h1 {
      font-size: 1.6rem;
      font-weight: 600;
      color: var(--s-text);
      margin: 0 0 8px;
    }
    .ug-hero p {
      color: var(--s-muted);
      font-size: 0.92rem;
      font-weight: 400;
      margin: 0 0 26px;
    }
    .ug-search-wrap {
      position: relative;
      max-width: 540px;
      margin: 0 auto;
    }
    .ug-search-wrap > i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--s-muted);
      font-size: 1rem;
      pointer-events: none;
    }
    .ug-search-input {
      width: 100%;
      padding: 13px 16px 13px 46px;
      border: 1px solid var(--s-border);
      border-radius: var(--s-radius-md);
      font-family: 'Inter', sans-serif;
      font-size: 0.92rem;
      background: var(--s-body-bg);
      color: var(--s-text);
      transition: border-color .15s, background .15s;
    }
    .ug-search-input:focus {
      outline: none;
      border-color: var(--s-primary);
      background: #fff;
    }

    /* Category grid */
    .ug-cat-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 16px;
      margin-bottom: 40px;
    }
    @media (max-width: 1280px) {
      .ug-cat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 860px) {
      .ug-cat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 540px) {
      .ug-cat-grid { grid-template-columns: 1fr; }
    }
    .ug-cat-card {
      display: flex;
      flex-direction: column;
      background: var(--s-card-bg);
      border: 1px solid var(--s-border);
      border-radius: var(--s-radius-md);
      padding: 24px;
      text-decoration: none;
      transition: border-color .15s, box-shadow .15s;
    }
    .ug-cat-card:hover {
      border-color: var(--s-text-2);
      box-shadow: var(--s-shadow-sm);
    }
    .ug-cat-card h3 {
      font-size: 1.02rem;
      font-weight: 600;
      color: var(--s-text);
      margin: 0 0 8px;
    }
    .ug-cat-card p {
      font-size: 0.86rem;
      color: var(--s-muted);
      margin: 0;
      line-height: 1.55;
      flex: 1 1 auto;
    }
    .ug-cat-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 18px;
      padding-top: 16px;
      border-top: 1px solid var(--s-border);
      font-size: 0.8rem;
    }
    .ug-cat-count {
      color: var(--s-muted);
      font-weight: 400;
    }
    .ug-cat-arrow {
      color: var(--s-primary);
      font-weight: 600;
    }

    /* Breadcrumb */
    .ug-breadcrumb {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
      font-size: 0.82rem;
      color: var(--s-muted);
      margin-bottom: 20px;
    }
    .ug-breadcrumb a,
    .ug-breadcrumb button {
      background: none;
      border: none;
      padding: 0;
      font-family: 'Inter', sans-serif;
      font-size: 0.82rem;
      color: var(--s-muted);
      cursor: pointer;
      text-decoration: none;
      transition: color .15s;
    }
    .ug-breadcrumb a:hover,
    .ug-breadcrumb button:hover { color: var(--s-primary); }
    .ug-breadcrumb .ug-crumb-sep { color: var(--s-border-2); }
    .ug-breadcrumb .ug-crumb-current {
      color: var(--s-text-2);
      font-weight: 500;
    }

    /* Category article list */
    .ug-cat-articles {
      max-width: 760px;
      margin: 0 auto;
      display: none;
    }
    .ug-cat-articles.ug-active { display: block; }
    .ug-cat-articles h2 {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--s-text);
      margin: 0 0 20px;
    }
    .ug-article-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 28px;
    }
    .ug-article-card {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      background: var(--s-card-bg);
      border: 1px solid var(--s-border);
      border-radius: var(--s-radius-md);
      padding: 18px 20px;
      text-decoration: none;
      cursor: pointer;
      transition: border-color .15s, box-shadow .15s;
    }
    .ug-article-card:hover {
      border-color: var(--s-primary);
      box-shadow: var(--s-shadow-sm);
    }
    .ug-article-icon {
      flex-shrink: 0;
      width: 28px;
      height: 28px;
      margin-top: 2px;
      border-radius: 50%;
      background: var(--s-primary);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.78rem;
    }
    .ug-article-card-body h3 {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--s-text);
      margin: 0 0 4px;
    }
    .ug-article-card-body p {
      margin: 0;
      font-size: 0.84rem;
      color: var(--s-muted);
      line-height: 1.55;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Article detail */
    .ug-article-detail {
      max-width: 1080px;
      margin: 0 auto;
      display: none;
    }
    .ug-article-detail.ug-active { display: block; }
    .ug-article-detail h1 {
      font-size: 1.55rem;
      font-weight: 600;
      color: var(--s-text);
      line-height: 1.35;
      margin: 0 0 24px;
    }
    .ug-article-layout {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 40px;
      align-items: start;
    }
    @media (max-width: 900px) {
      .ug-article-layout { grid-template-columns: 1fr; }
    }
    .ug-article-sidebar {
      display: flex;
      flex-direction: column;
      gap: 20px;
      position: sticky;
      top: 24px;
    }
    .ug-article-steps {
      margin: 0;
      padding-left: 22px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .ug-article-steps li {
      font-size: 0.92rem;
      color: var(--s-text-2);
      line-height: 1.7;
      padding-left: 4px;
    }
    .ug-article-steps li::marker {
      font-weight: 600;
      color: var(--s-primary);
    }

    /* Related articles */
    .ug-article-related {
      background: var(--s-card-bg);
      border: 1px solid var(--s-border);
      border-radius: var(--s-radius-md);
      padding: 20px;
    }
    .ug-article-related-label {
      display: block;
      font-size: 0.76rem;
      font-weight: 600;
      color: var(--s-muted);
      text-transform: uppercase;
      letter-spacing: .04em;
      margin-bottom: 12px;
    }
    .ug-article-related-links {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .ug-article-related-chip {
      display: inline-flex;
      align-self: flex-start;
      padding: 0;
      background: none;
      border: none;
      color: var(--s-primary);
      font-size: 0.88rem;
      font-weight: 500;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      text-align: left;
      transition: color .15s;
    }
    .ug-article-related-chip:hover { color: var(--s-accent2); text-decoration: underline; }

    /* Still need help CTA */
    .ug-help-cta {
      text-align: center;
      background: rgba(0,124,191,.05);
      border: 1px solid rgba(0,124,191,.15);
      border-radius: var(--s-radius-md);
      padding: 28px 24px;
    }
    .ug-help-cta strong {
      display: block;
      font-size: 1rem;
      font-weight: 600;
      color: var(--s-text);
      margin-bottom: 6px;
    }
    .ug-help-cta p {
      margin: 0 0 18px;
      font-size: 0.86rem;
      color: var(--s-muted);
    }
    .ug-help-cta-btn {
      display: inline-block;
      padding: 10px 22px;
      background: var(--s-primary);
      color: #fff;
      border-radius: var(--s-radius-sm);
      font-size: 0.86rem;
      font-weight: 600;
      text-decoration: none;
      transition: background .15s;
    }
    .ug-help-cta-btn:hover { background: var(--s-primary-dark); }

    /* Search results dropdown */
    #ugSearchResults {
      display: none;
      position: absolute;
      top: calc(100% + 10px);
      left: 50%;
      transform: translateX(-50%);
      width: min(720px, 90vw);
      max-height: 420px;
      overflow-y: auto;
      background: var(--s-card-bg);
      border: 1px solid var(--s-border);
      border-radius: var(--s-radius-md);
      box-shadow: var(--s-shadow-lg);
      padding: 14px;
      text-align: left;
      z-index: 40;
    }
    #ugSearchResults.ug-active { display: block; }
    .ug-search-results-header {
      font-size: 0.78rem;
      color: var(--s-muted);
      margin: 2px 4px 12px;
    }
    .ug-search-results-header strong { color: var(--s-text); font-weight: 600; }
    .ug-search-results-grid {
      display: flex;
      flex-direction: column;
    }
    @media (max-width: 600px) {
      #ugSearchResults { width: 92vw; }
    }
    .ug-search-result {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      width: 100%;
      text-align: left;
      padding: 13px 14px;
      background: transparent;
      border: none;
      border-bottom: 1px solid var(--s-border);
      border-radius: 0;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      transition: background .15s;
    }
    .ug-search-result:first-child { border-radius: var(--s-radius-sm) var(--s-radius-sm) 0 0; }
    .ug-search-result:last-child { border-bottom: none; border-radius: 0 0 var(--s-radius-sm) var(--s-radius-sm); }
    .ug-search-result:only-child { border-radius: var(--s-radius-sm); }
    .ug-search-result:hover {
      background: var(--s-body-bg);
    }
    .ug-search-result-title {
      flex: 1;
      min-width: 0;
      font-size: 0.86rem;
      font-weight: 500;
      color: var(--s-text);
      line-height: 1.45;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .ug-search-result-cat {
      font-size: 0.74rem;
      color: var(--s-muted);
      white-space: nowrap;
      flex-shrink: 0;
    }
    .ug-search-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      padding: 36px 24px;
      color: var(--s-muted);
    }
    .ug-search-empty-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--s-body-bg);
      border: 1px solid var(--s-border);
      color: var(--s-muted);
      font-size: 1.3rem;
      margin-bottom: 16px;
    }
    .ug-search-empty strong {
      display: block;
      font-size: 0.96rem;
      font-weight: 600;
      color: var(--s-text);
      margin-bottom: 6px;
    }
    .ug-search-empty p {
      margin: 0 0 18px;
      font-size: 0.84rem;
      line-height: 1.6;
      max-width: 340px;
    }
    .ug-search-empty p strong { display: inline; font-size: inherit; font-weight: 600; margin: 0; color: var(--s-text); }
    .ug-search-empty-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 18px;
      border-radius: var(--s-radius-sm);
      border: 1px solid var(--s-border);
      background: var(--s-card-bg);
      color: var(--s-primary);
      font-size: 0.84rem;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      transition: border-color .15s, background .15s, color .15s;
    }
    .ug-search-empty-btn:hover {
      border-color: var(--s-primary);
      background: var(--s-primary);
      color: #fff;
    }

    /* Search highlight */
    mark.ug-mark {
      background: #fef08a;
      color: inherit;
      border-radius: 2px;
      padding: 0 1px;
    }

    /* Help & support cards */
    .ug-support-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
      gap: 14px;
      margin-top: 28px;
    }
    .ug-support-card {
      background: var(--s-card-bg);
      border: 1px solid var(--s-border);
      border-radius: var(--s-radius-md);
      padding: 24px;
      text-decoration: none;
      transition: border-color .15s, box-shadow .15s;
    }
    .ug-support-card:hover { border-color: var(--s-text-2); box-shadow: var(--s-shadow-sm); }
    .ug-support-card h4 { font-size: 0.96rem; font-weight: 600; color: var(--s-text); margin: 0 0 6px; }
    .ug-support-card p { font-size: 0.84rem; color: var(--s-muted); margin: 0; }
    .ug-support-card .ug-support-link {
      display: inline-block;
      margin-top: 14px;
      font-size: 0.84rem;
      font-weight: 600;
      color: var(--s-primary);
    }

    @media (max-width: 768px) {
      .ug-hero { padding: 32px 16px; }
      .ug-cat-grid { grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); }
    }
  </style>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">

  <!-- ── Hero + Search ── -->
  <div class="ug-hero">
    <h1>How can we help you?</h1>
    <p>Search for a topic, or browse the categories below to learn how everything in Studium works.</p>
    <div class="ug-search-wrap">
      <i class="bi bi-search"></i>
      <input type="text" id="ugSearch" class="ug-search-input" placeholder="Type a question, e.g. &quot;How to start an NGN exam&quot;" oninput="ugSearch()" autocomplete="off">
      <div id="ugSearchResults">
        <div class="ug-search-results-header" id="ugSearchResultsHeader"></div>
        <div class="ug-search-results-grid" id="ugSearchResultsGrid"></div>
      </div>
    </div>
  </div>

  <!-- ── Category Grid ── -->
  <div class="ug-cat-grid" id="ugCatGrid">
    <?php foreach ($guideSections as $sec): ?>
    <a href="#<?php echo $sec['id']; ?>" class="ug-cat-card" onclick="ugShowCategoryArticles('<?php echo $sec['id']; ?>'); return false;">
      <h3><?php echo htmlspecialchars($sec['title']); ?></h3>
      <p><?php echo $sec['desc']; ?></p>
      <div class="ug-cat-footer">
        <span class="ug-cat-count"><?php echo count($sec['faqs']); ?> articles</span>
        <span class="ug-cat-arrow">&rarr;</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- ── Category Article Lists ── -->
  <?php foreach ($guideSections as $sec): ?>
  <div class="ug-cat-articles" id="<?php echo $sec['id']; ?>-articles">
    <div class="ug-breadcrumb">
      <button type="button" onclick="ugBackToCategories()">Help Center</button>
      <span class="ug-crumb-sep">/</span>
      <span class="ug-crumb-current"><?php echo htmlspecialchars($sec['title']); ?></span>
    </div>
    <h2>Questions in <?php echo htmlspecialchars($sec['title']); ?></h2>

    <div class="ug-article-list">
      <?php foreach ($sec['faqs'] as $faq):
        $excerpt = trim(preg_replace('/\s+/', ' ', strip_tags(implode(' ', $faq['steps']))));
        if (mb_strlen($excerpt) > 110) {
            $excerpt = mb_substr($excerpt, 0, 110) . '…';
        }
      ?>
      <a href="#<?php echo $faq['id']; ?>" class="ug-article-card" onclick="ugShowArticle('<?php echo $faq['id']; ?>'); return false;">
        <span class="ug-article-icon"><i class="bi bi-question-lg"></i></span>
        <span class="ug-article-card-body">
          <h3><?php echo htmlspecialchars($faq['q']); ?></h3>
          <p><?php echo htmlspecialchars($excerpt); ?></p>
        </span>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($sec['id'] === 'cat-support'): ?>
    <div class="ug-support-grid">
      <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter" target="_blank" rel="noopener noreferrer" class="ug-support-card">
        <h4>Message Us on Facebook</h4>
        <p>Get help with your account, payments, or technical issues.</p>
        <span class="ug-support-link">Open Facebook Page &rarr;</span>
      </a>
      <a href="../../img/userguide.mp4" target="_blank" rel="noopener noreferrer" class="ug-support-card">
        <h4>Watch Video Walkthrough</h4>
        <p>Prefer a video? Watch our quick guided tour of the dashboard.</p>
        <span class="ug-support-link">Watch Video &rarr;</span>
      </a>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <!-- ── Article Detail Pages ── -->
  <?php foreach ($guideSections as $sec): foreach ($sec['faqs'] as $faq):
    $searchBlob = strtolower($faq['q'] . ' ' . strip_tags(implode(' ', $faq['steps'])));
  ?>
  <article class="ug-article-detail" id="article-<?php echo $faq['id']; ?>" data-search="<?php echo htmlspecialchars($searchBlob); ?>" data-title="<?php echo htmlspecialchars($faq['q']); ?>" data-category="<?php echo htmlspecialchars($sec['title']); ?>">
    <div class="ug-breadcrumb">
      <button type="button" onclick="ugBackToCategories()">Help Center</button>
      <span class="ug-crumb-sep">/</span>
      <button type="button" onclick="ugShowCategoryArticles('<?php echo $sec['id']; ?>')"><?php echo htmlspecialchars($sec['title']); ?></button>
      <span class="ug-crumb-sep">/</span>
      <span class="ug-crumb-current"><?php echo htmlspecialchars($faq['q']); ?></span>
    </div>

    <h1><?php echo htmlspecialchars($faq['q']); ?></h1>

    <div class="ug-article-layout">
      <div class="ug-article-main">
        <ol class="ug-article-steps">
          <?php foreach ($faq['steps'] as $step): ?>
          <li><?php echo $step; ?></li>
          <?php endforeach; ?>
        </ol>
      </div>

      <div class="ug-article-sidebar">
        <?php if (!empty($faq['related'])): ?>
        <div class="ug-article-related">
          <span class="ug-article-related-label">Related articles</span>
          <div class="ug-article-related-links">
            <?php foreach ($faq['related'] as $relId):
              if (!isset($faqLookup[$relId])) continue;
            ?>
            <button type="button" class="ug-article-related-chip" onclick="ugShowArticle('<?php echo $relId; ?>')"><?php echo htmlspecialchars($faqLookup[$relId]); ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="ug-help-cta">
          <strong>Still need help?</strong>
          <p>Our support team is ready to assist you anytime.</p>
          <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter" target="_blank" rel="noopener noreferrer" class="ug-help-cta-btn">Message Support</a>
        </div>
      </div>
    </div>
  </article>
  <?php endforeach; endforeach; ?>

</main>

<script>
function ugHideAllViews() {
  document.getElementById('ugCatGrid').style.display = 'none';
  document.querySelectorAll('.ug-cat-articles').forEach(function (el) { el.classList.remove('ug-active'); });
  document.querySelectorAll('.ug-article-detail').forEach(function (el) { el.classList.remove('ug-active'); });
}

function ugCloseSearchDropdown() {
  document.getElementById('ugSearchResults').classList.remove('ug-active');
}

function ugClearSearch() {
  const search = document.getElementById('ugSearch');
  if (search) search.value = '';
  ugCloseSearchDropdown();
}

function ugBackToCategories() {
  ugClearSearch();
  ugHideAllViews();
  document.getElementById('ugCatGrid').style.display = '';
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (history.replaceState) history.replaceState(null, '', location.pathname);
}

function ugShowCategoryArticles(catId) {
  ugClearSearch();
  ugHideAllViews();
  const el = document.getElementById(catId + '-articles');
  if (!el) { ugBackToCategories(); return; }
  el.classList.add('ug-active');
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (history.replaceState) history.replaceState(null, '', '#' + catId);
}

function ugShowArticle(faqId) {
  ugClearSearch();
  ugHideAllViews();
  const el = document.getElementById('article-' + faqId);
  if (!el) { ugBackToCategories(); return; }
  el.classList.add('ug-active');
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (history.replaceState) history.replaceState(null, '', '#' + faqId);
}

document.addEventListener('DOMContentLoaded', function () {
  const hash = location.hash.replace('#', '');
  if (!hash) return;

  if (document.getElementById('article-' + hash)) {
    ugShowArticle(hash);
  } else if (document.getElementById(hash + '-articles')) {
    ugShowCategoryArticles(hash);
  }
});

function ugEscapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function ugHighlightString(text, query) {
  const escaped = ugEscapeHtml(text);
  const lower   = escaped.toLowerCase();
  const q       = query.toLowerCase();
  let result = '', lastIndex = 0, idx;

  while ((idx = lower.indexOf(q, lastIndex)) !== -1) {
    result += escaped.slice(lastIndex, idx);
    result += '<mark class="ug-mark">' + escaped.slice(idx, idx + q.length) + '</mark>';
    lastIndex = idx + q.length;
  }
  result += escaped.slice(lastIndex);
  return result;
}

function ugEscapeRegex(text) {
  return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function ugSearch() {
  const query   = document.getElementById('ugSearch').value.trim();
  const results = document.getElementById('ugSearchResults');
  const grid    = document.getElementById('ugSearchResultsGrid');
  const header  = document.getElementById('ugSearchResultsHeader');

  if (query === '') {
    ugCloseSearchDropdown();
    return;
  }

  const re = new RegExp('\\b' + ugEscapeRegex(query.toLowerCase()), 'i');
  grid.innerHTML = '';

  let count = 0;

  document.querySelectorAll('.ug-article-detail').forEach(function (article) {
    const haystack = article.dataset.search || '';
    if (!re.test(haystack)) return;
    count++;

    const faqId = article.id.replace('article-', '');

    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'ug-search-result';
    item.addEventListener('click', function () { ugShowArticle(faqId); });

    const titleEl = document.createElement('div');
    titleEl.className = 'ug-search-result-title';
    titleEl.innerHTML = ugHighlightString(article.dataset.title, query.toLowerCase());

    const catEl = document.createElement('div');
    catEl.className = 'ug-search-result-cat';
    catEl.textContent = 'in ' + article.dataset.category;

    item.appendChild(titleEl);
    item.appendChild(catEl);
    grid.appendChild(item);
  });

  if (count === 0) {
    header.innerHTML = '';
    grid.innerHTML = '<div class="ug-search-empty">' +
      '<div class="ug-search-empty-icon"><i class="bi bi-search"></i></div>' +
      '<strong>No results found</strong>' +
      '<p>We couldn&rsquo;t find anything for &ldquo;<strong>' + ugEscapeHtml(query) + '</strong>&rdquo;. Try a different keyword or browse our help categories instead.</p>' +
      '<button type="button" class="ug-search-empty-btn" onclick="ugBackToCategories()"><i class="bi bi-grid"></i> Browse all categories</button>' +
      '</div>';
  } else {
    header.innerHTML = count + ' result' + (count === 1 ? '' : 's') + ' for &ldquo;<strong>' + ugEscapeHtml(query) + '</strong>&rdquo;';
  }

  results.classList.add('ug-active');
}

document.getElementById('ugSearch').addEventListener('focus', function () {
  if (this.value.trim() !== '') ugSearch();
});

document.getElementById('ugSearch').addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    this.value = '';
    ugCloseSearchDropdown();
    this.blur();
  }
});

document.addEventListener('click', function (e) {
  const wrap = document.querySelector('.ug-search-wrap');
  if (wrap && !wrap.contains(e.target)) {
    ugCloseSearchDropdown();
  }
});
</script>

</body>
</html>
