<!DOCTYPE html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Testimonial</title>
	<style type="text/css">
		.containera {
			position: relative;
			width: 150%;
			min-height: 450px;
			margin-top: -53%;
			margin-left: 15%;
		}

		.containera .contents-wraper {
			width: 70%;
			min-height: inherit;
			margin: 30px auto;
			text-align: center;
		}

		.contents-wraper .testRow {
			width: 100%;
			min-height: inherit;
			position: relative;
			overflow: hidden;
		}

		.testRow .testItem {
			width: 100%;
			height: 100%;
			position: absolute;
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: column;
		}

		.testRow .testItem:not(.active) {
			top: 0;
			left: -100%;
		}

		.testRow .testItem h5 {
			font-size: 25px;

		}

		.testRow .testItem h6 {}

		.testRow .testItem p {
			font-size: 15px;
			letter-spacing: 1px;
			line-height: 1.2;
			padding: 10px;
			font-style: italic;
		}

		.contents-wraper .indicators {
			position: absolute;
			bottom: 30px;
			left: 50%;
			transform: translateX(-50%);
			padding: 5px;
			cursor: pointer;
		}

		.contents-wraper .indicators .dot {
			width: 15px;
			height: 15px;
			margin: 0px 3px;
			border: 3px solid #aaa;
			border-radius: 50%;
			display: inline-block;
			transition: background-color 1s ease;
		}

		.contents-wraper .indicators .active {
			background-color: #006994;
		}

		@keyframes next1 {
			from {
				left: 0%;
			}

			to {
				left: -100%;
			}
		}

		@keyframes next2 {
			from {
				left: 100%;
			}

			to {
				left: 0%;
			}
		}

		@keyframes prev1 {
			from {
				left: 0%;
			}

			to {
				left: 100%;
			}
		}

		@keyframes prev2 {
			from {
				left: -100%;
			}

			to {
				left: 0%;
			}
		}

		@media(max-width: 550px) {
			.containera .contents-wraper {
				width: 90%;
			}

			.contents-wraper .header h1 {
				font-size: 32px;
			}

			.testRow .testItem h5 {
				font-size: 26px;
			}

			.testRow .testItem p {
				font-size: 16px;
				letter-spacing: initial;
				line-height: initial;
			}

		}
	</style>
	<link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet">
	<script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>

<body>
	<div class="containera">

		<div class="contents-wraper">
			<section class="testRow">
				<div class="testItem active">
					<div class=" p-6">
						<p class="italic text-justify my-2 text-gray-700 ">
							"I am very honored and proud to be a part of NCLEX amplified review that helped me in my
							NCLEX journey. I thank God for the guidance that he has given to me during my review and the
							exam itself. This achievement is only a beginning for another more learning in the future. I
							look forward for another chapter of my life using my profession as a nurse. I am truly happy
							to use my knowledge to help my future patients to the best that I can give."
						</p>
						<div class="flex items-center justify-between w-full">
							<div class="flex items-center  space-x-3">
								<img src="login/testi/img/Earlson.jpg" alt="Testimonial Image"
									class="w-14 h-14 object-cover rounded-full">
								<div class="flex flex-col">
									<h6 class="font-bold text-left m-0">Earlson H Del Rosario</h6>
									<h6 class="font-bold text-left text-base text-gray-700 m-0">PHRN, USRN</h6>
								</div>

							</div>
							<div class="text-2xl flex justify-right text-yellow-500 text-right">★★★★★</div>
						</div>




					</div>
				</div>

				<?php
				include 'config.php';

				$sql = "select * from testimonial ORDER BY id DESC";
				$result = mysqli_query($con, $sql);

				while ($row = mysqli_fetch_array($result)) {

					?>
					<div class="testItem">
						<div class=" p-6">
							<p class="italic text-justify my-2 text-gray-700 ">
								<?php echo $row['message']; ?>
							</p>
							<div class="flex items-center justify-between w-full">
								<div class="flex items-center  space-x-3">
									<img src="<?php echo $row['image']; ?>" alt="Testimonial Image"
										class="w-14 h-14 object-cover rounded-full">
									<div class="flex flex-col">
										<h6 class="font-bold text-left m-0"><?php echo $row['name']; ?></h6>
										<h6 class="font-bold text-left text-base text-gray-700 m-0">
											<?php echo $row['credinial']; ?>
										</h6>
									</div>

								</div>
								<div class="text-2xl flex justify-right text-yellow-500 text-right">★★★★★</div>
							</div>




						</div>
					</div>
				<?php } ?>
			</section>
		</div>

	</div>

	<script type="text/javascript">

		// Access the testimonials
		let testSlide = document.querySelectorAll('.testItem');
		// Access the indicators
		let dots = document.querySelectorAll('.dot');

		var counter = 0;

		// Add click event to the indicators
		function switchTest(currentTest) {
			currentTest.classList.add('active');
			var testId = currentTest.getAttribute('attr');
			if (testId > counter) {
				testSlide[counter].style.animation = 'next1 1s ease-in forwards';
				counter = testId;
				testSlide[counter].style.animation = 'next2 1s ease-in forwards';
			}
			else if (testId == counter) { return; }
			else {
				testSlide[counter].style.animation = 'prev1 1s ease-in forwards';
				counter = testId;
				testSlide[counter].style.animation = 'prev2 1s ease-in forwards';
			}
			indicators();
		}

		// Add and remove active class from the indicators
		function indicators() {
			for (i = 0; i < dots.length; i++) {
				dots[i].className = dots[i].className.replace(' active', '');
			}
			dots[counter].className += ' active';
		}

		// Code for auto sliding
		function slideNext() {
			testSlide[counter].style.animation = 'next1 1s ease-in forwards';
			if (counter >= testSlide.length - 1) {
				counter = 0;
			}
			else {
				counter++;
			}
			testSlide[counter].style.animation = 'next2 1s ease-in forwards';
			indicators();
		}
		function autoSliding() {
			deleteInterval = setInterval(timer, 4000);
			function timer() {
				slideNext();
				indicators();
			}
		}
		autoSliding();

		// Stop auto sliding when mouse is over the indicators
		const containera = document.querySelector('.indicators');
		container.addEventListener('mouseover', pause);
		function pause() {
			clearInterval(deleteInterval);
		}

		// Resume sliding when mouse is out of the indicators
		container.addEventListener('mouseout', autoSliding);

	</script>
</body>

</html>