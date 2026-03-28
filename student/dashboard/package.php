<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <title>Pricing Table</title>
  <style>
    .pricing {
      font-family: "Archivo Black", serif;
      font-weight: 400;
      font-style: normal;
    }
  </style>
</head>

<body class="bg-gradient-to-br from-white to-sky-100 min-h-screen flex flex-col justify-between">
  <nav class="bg-white shadow fixed w-full z-10">
    <div class="container mx-auto flex justify-between items-center p-4">
      <div class="logo-container">
        <img src="../../img/logo.png" alt="NCLEX Logo" class="h-24 md:h-20 lg:h-16" />
      </div>
      <div class="md:hidden">
        <button id="mobile-menu-toggle" class="text-gray-700 focus:outline-none">
          <i class="fas fa-bars"></i>
        </button>
      </div>
      <div class="hidden md:flex space-x-4">
        <a href="javascript:window.history.back()" class="text-gray-700 hover:text-[#3954A2]">Home</a>
        <a href="#" class="text-gray-700 hover:text-[#3954A2]" id="pdf-reviewer-toggle">PDF Reviewer</a>
        <a href="https://www.facebook.com/NCLEX.Amplified.Technical" target="_blank" rel="noopener noreferrer"
          class="text-gray-700 hover:text-[#3954A2]">Contact</a>
      </div>
    </div>
  </nav>

  <div id="mobile-menu" class="fixed inset-0 bg-white z-20 hidden md:hidden">
    <div class="flex justify-end p-4">
      <button id="mobile-menu-close" class="text-gray-700">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="flex flex-col items-center space-y-4 mt-12">
      <a href="javascript:window.history.back()" class="text-gray-700 hover:text-[#3954A2]">Go Back</a>
      <a href="#" class="text-gray-700 hover:text-[#3954A2]" id="pdf-reviewer-toggle">PDF Reviewer</a>
      <a href="#" class="text-gray-700 hover:text-[#3954A2]">Contact</a>
    </div>
  </div>

  <div class="main-content mt-32 md:mt-26 lg:mt-24 p-4">
    <div class="mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-4">
      <div
        class="bg-white rounded-lg shadow-md p-6 transition-transform duration-300 hover:shadow-lg flex flex-col justify-between shadow-[#3954a2]">
        <div class="text-left mb-4">
          <h2 class="text-lg font-bold text-[#3954A2]">Package 1</h2>

          <div class="space-y-2 text-left">
            <div>Unlimited NCLEX Review until you PASS</div>
            <div class="text-4xl py-2 font-bold pricing">₱6,999</div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 24/7
              dashboard Access
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Lecture: 8am
              to 12:30 <b>(M-F)</b>
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Weekly Recap
              8am-12nn
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Free Study
              Plan Consultation
            </div>

            <div style="
                  border: 2px solid #3954a2;
                  padding: 10px;
                  border-radius: 10px;
                ">
              <div class="text-[#3954A2] font-bold">Free PDF Reviewers:</div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> La Charity
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX
                Amplified Q/A Compilation Edition 1
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Saunders
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Lippincott
                14th edition
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Mosby
                Comprehensive Book
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Notes
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Cram Questionnaires
              </div>
            </div>

            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 250 Items
              Exclusive Questionnaires (Based on latest Trend in NCLEX)
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Live
              Testimony
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Passer
              Certificate
            </div>
          </div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank"
          class="bg-[#3954A2] text-white font-semibold py-2 rounded mt-4 w-full text-center">ENROLL NOW</a>
      </div>

      <div
        class="bg-white rounded-lg shadow-md p-6 transition-transform duration-300 hover:shadow-lg flex flex-col justify-between shadow-[#3954a2]">
        <div class="text-left mb-4">
          <h2 class="text-lg font-bold text-[#3954A2]">Package 2</h2>

          <div class="space-y-2 text-left">
            <div>Unlimited NCLEX Review until you PASS</div>
            <div class="text-4xl py-2 font-bold pricing">₱8,499</div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 24/7
              dashboard Access
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Lecture: 8am
              to 12:30 <b>(M-F)</b>
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Weekly Recap
              8am-12nn
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Free Study
              Plan Consultation
            </div>

            <div style="
                  border: 2px solid #3954a2;
                  padding: 10px;
                  border-radius: 10px;
                ">
              <div class="text-[#3954A2] font-bold">Free PDF Reviewers:</div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> La Charity
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX
                Amplified Q/A Compilation Edition 1
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Saunders
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Lippincott
                14th edition
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Mosby
                Comprehensive Book
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Notes
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Cram Questionnaires
              </div>
            </div>

            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Our Very Own
              NCLEX Codex (PDF)
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX
              AMPLIFIED Q/A EDITION 2
            </div>

            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 250 Items
              Exclusive Questionnaires (Based on latest Trend in NCLEX)
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Live
              Testimony
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Passer
              Certificate
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Mind
              Conditioning Consultation
            </div>
          </div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank"
          class="bg-[#3954A2] text-white font-semibold py-2 rounded mt-4 w-full text-center">ENROLL NOW</a>
      </div>

      <div
        class="bg-white rounded-lg shadow-md p-6 transition-transform duration-300 hover:shadow-lg flex flex-col justify-between shadow-[#3954a2]">
        <div class="text-left mb-4">
          <h2 class="text-lg font-bold text-[#3954A2]">Package 3</h2>

          <div class="space-y-2 text-left">
            <div>Unlimited NCLEX Review until you PASS</div>
            <div class="text-4xl py-2 font-bold pricing">₱9,999</div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 24/7
              dashboard Access
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Lecture: 8am
              to 12:30 <b>(M-F)</b>
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Weekly Recap
              8am-12nn
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Free Study
              Plan Consultation
            </div>

            <div style="
                  border: 2px solid #3954a2;
                  padding: 10px;
                  border-radius: 10px;
                ">
              <div class="text-[#3954A2] font-bold">Free PDF Reviewers:</div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> La Charity
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX
                Amplified Q/A Compilation Edition 1
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Saunders
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Lippincott
                14th edition
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Mosby
                Comprehensive Book
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Notes
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Cram Questionnaires
              </div>
            </div>

            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Our Very Own
              NCLEX Codex (PDF)
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX
              AMPLIFIED Q/A EDITION 2
            </div>

            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 250 Items
              Exclusive Questionnaires (Based on latest Trend in NCLEX)
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Live
              Testimony
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Passer
              Certificate
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Mind
              Conditioning Consultation
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Our Pathway
              Processing Center 5% - 10% Discount
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Free Initial
              Assesment from Our Pathway Processing Center
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 3 months
              Studium CAT
            </div>
          </div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank"
          class="bg-[#3954A2] text-white font-semibold py-2 rounded mt-4 w-full text-center">ENROLL NOW</a>
      </div>

      <div
        class="bg-white rounded-lg shadow-md p-6 transition-transform duration-300 hover:shadow-lg flex flex-col justify-between shadow-[#3954a2]">
        <div class="text-left mb-4">
          <h2 class="text-lg font-bold text-[#3954A2]">Package 4</h2>

          <div class="space-y-2 text-left">
            <div>4 months Review + Package 1</div>
            <br />
            <div class="text-4xl py-2 font-bold pricing">₱2,499</div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 24/7
              dashboard Access
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Lecture: 8am
              to 12:30 <b>(M-F)</b>
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Weekly Recap
              8am-12nn
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Free Study
              Plan Consultation
            </div>

            <div style="
                  border: 2px solid #3954a2;
                  padding: 10px;
                  border-radius: 10px;
                ">
              <div class="text-[#3954A2] font-bold">Free PDF Reviewers:</div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> La Charity
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX
                Amplified Q/A Compilation Edition 1
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Saunders
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Lippincott
                14th edition
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Mosby
                Comprehensive Book
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Notes
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Cram Questionnaires
              </div>
            </div>

            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 250 Items
              Exclusive Questionnaires (Based on latest Trend in NCLEX)
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Live
              Testimony
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Passer
              Certificate
            </div>
          </div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank"
          class="bg-[#3954A2] text-white font-semibold py-2 rounded mt-4 w-full text-center">ENROLL NOW</a>
      </div>

      <div
        class="bg-white rounded-lg shadow-md p-6 transition-transform duration-300 hover:shadow-lg flex flex-col justify-between shadow-[#3954a2]">
        <div class="text-left mb-4">
          <h2 class="text-lg font-bold text-[#3954A2]">Package 5</h2>

          <div class="space-y-2 text-left">
            <div>8 months Review + Package 1</div>
            <br />
            <div class="text-4xl py-2 font-bold pricing">₱3,499</div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 24/7
              dashboard Access
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Lecture: 8am
              to 12:30 <b>(M-F)</b>
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Weekly Recap
              8am-12nn
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Free Study
              Plan Consultation
            </div>

            <div style="
                  border: 2px solid #3954a2;
                  padding: 10px;
                  border-radius: 10px;
                ">
              <div class="text-[#3954A2] font-bold">Free PDF Reviewers:</div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> La Charity
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX
                Amplified Q/A Compilation Edition 1
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Saunders
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Lippincott
                14th edition
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Mosby
                Comprehensive Book
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Notes
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Cram Questionnaires
              </div>
            </div>

            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 250 Items
              Exclusive Questionnaires (Based on latest Trend in NCLEX)
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Live
              Testimony
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Passer
              Certificate
            </div>
          </div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank"
          class="bg-[#3954A2] text-white font-semibold py-2 rounded mt-4 w-full text-center">ENROLL NOW</a>
      </div>
      <div
        class="bg-white rounded-lg shadow-md p-6 transition-transform duration-300 hover:shadow-lg flex flex-col justify-between shadow-[#3954a2]">
        <div class="text-left mb-4">
          <h2 class="text-lg font-bold text-[#3954A2]">Package 6</h2>

          <div class="space-y-2 text-left">
            <div>1 year Review + Package 1</div>
            <br />
            <div class="text-4xl py-2 font-bold pricing">₱4,499</div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 24/7
              dashboard Access
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Lecture: 8am
              to 12:30 <b>(M-F)</b>
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Weekly Recap
              8am-12nn
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Free Study
              Plan Consultation
            </div>

            <div style="
                  border: 2px solid #3954a2;
                  padding: 10px;
                  border-radius: 10px;
                ">
              <div class="text-[#3954A2] font-bold">Free PDF Reviewers:</div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> La Charity
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX
                Amplified Q/A Compilation Edition 1
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Saunders
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Lippincott
                14th edition
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> Mosby
                Comprehensive Book
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Notes
              </div>
              <div>
                <i class="fa fa-check-square-o text-[#DC292A]"></i> NCLEX RN
                Cram Questionnaires
              </div>
            </div>

            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> 250 Items
              Exclusive Questionnaires (Based on latest Trend in NCLEX)
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Live
              Testimony
            </div>
            <div>
              <i class="fa fa-check-square-o text-[#DC292A]"></i> Passer
              Certificate
            </div>
          </div>
        </div>
        <a href="https://www.facebook.com/NCLEX.Amplified.Payment.Transaction" target="_blank"
          class="bg-[#3954A2] text-white font-semibold py-2 rounded mt-4 w-full text-center">ENROLL NOW</a>
      </div>
    </div>
  </div>

  <footer class="bg-[#3954A2] text-white p-6">
    <div class="container mx-auto text-center">

      <p>&copy; 2025 NCLEX Amplified. All rights reserved.</p>
    </div>
  </footer>

  <script>
    document
      .getElementById("mobile-menu-toggle")
      .addEventListener("click", function () {
        document.getElementById("mobile-menu").classList.toggle("hidden");
      });

    document
      .getElementById("mobile-menu-close")
      .addEventListener("click", function () {
        document.getElementById("mobile-menu").classList.add("hidden");
      });

    document
      .getElementById("pdf-reviewer-toggle")
      .addEventListener("click", function () {
        Swal.fire({
          title: "PDF Reviewer",
          html: `
                    <strong>Edition 1: ₱2999</strong><br>
                    <strong>Edition 2: ₱2499</strong><br>
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
                `,
          showCloseButton: true,
          focusConfirm: false,
          confirmButtonText: "Close",
          confirmButtonAriaLabel: "Close this dialog",
        });
      });

    // Automatic SweetAlert popup
    Swal.fire({
      title: 'Coming Soon!',
      text: 'This subscription will be coming soon.',
      icon: 'info',
      confirmButtonText: 'Got it',
    }).then((result) => {
      if (result.isConfirmed) {
        // Navigate back
        window.history.back();
      }
    });
  </script>
</body>

</html>