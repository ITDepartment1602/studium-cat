-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 01:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u436962267_studium`
--

-- --------------------------------------------------------

--
-- Table structure for table `temporary_exam_result`
--

CREATE TABLE `temporary_exam_result` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `examTaken` int(11) DEFAULT NULL,
  `question_uid` varchar(255) DEFAULT NULL,
  `question_type` varchar(50) DEFAULT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `system` varchar(255) DEFAULT NULL,
  `cnc` varchar(255) DEFAULT NULL,
  `dlevel` varchar(255) DEFAULT NULL,
  `user_answer` text DEFAULT NULL,
  `correct_answer` text DEFAULT NULL,
  `isCorrect` tinyint(1) DEFAULT 0,
  `score` float DEFAULT 0,
  `earned_points` int(11) DEFAULT 0,
  `max_points` int(11) DEFAULT 1,
  `rationale` text DEFAULT NULL,
  `question_number` int(11) DEFAULT NULL,
  `time_taken` int(11) DEFAULT NULL,
  `totalTime` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temporary_exam_result`
--

INSERT INTO `temporary_exam_result` (`id`, `student_id`, `examTaken`, `question_uid`, `question_type`, `topic`, `system`, `cnc`, `dlevel`, `user_answer`, `correct_answer`, `isCorrect`, `score`, `earned_points`, `max_points`, `rationale`, `question_number`, `time_taken`, `totalTime`, `timestamp`) VALUES
(85, 45, 1102, 'dragndrop-6', 'dragndrop', 'Immunology adaptive immune response, pathogens', '', '', '', '[\"Immune\",\"Hyptehelegical system\"]', '[\"adaptive immune response\",\"pathogens\"]', 0, 0, 0, 2, 'Adaptive immune response is carried out by lymphocytes to target and destroy pathogens.', 1, 45, 44, '2026-03-26 06:58:51'),
(86, 45, 1102, 'dragndrop-10', 'dragndrop', 'Immunology memory, pathogen', '', '', '', '[\"memory\",\"pathogen\"]', '[\"memory\",\"pathogen\"]', 0, 1, 2, 2, 'Memory cells provide long-term immunity by remembering pathogens encountered before.', 2, 10, 99, '2026-03-26 06:59:47'),
(87, 45, 1102, 'highlight-2', 'highlight', 'Respiratory | Droplet Precautions', '', '', '', '[\"inflamed tonsils\",\"sore throat\"]', '[\"cough\",\"sore throat\",\"temperature 38.5\\u00b0C\",\"nasal discharge\",\"positive influenza A\"]', 0, 0, 0, 5, 'Droplet precautions are used for conditions transmitted through large respiratory droplets, such as influenza.', 3, 6, 116, '2026-03-26 07:00:04'),
(118, 45, 1108, 'mmr-2', 'mmr', 'Immunology adaptive immune, B', '', '', '', '[\"B\",\"T\"]', '[\"adaptive immune\",\"B\"]', 0, 0, 0, 2, 'The adaptive immune response involves B cells producing specific antibodies.', 1, 38, 37, '2026-03-26 07:18:30'),
(119, 45, 1109, 'dragndrop-7', 'dragndrop', 'Immunology T, infected', '', '', '', '[\"infected\",\"antibody\"]', '[\"T\",\"infected\"]', 0, 0, 0, 2, 'T cells mature in the thymus and play a direct role in destroying infected or abnormal cells.', 1, 4, 3, '2026-03-26 07:19:04'),
(120, 45, 1110, 'mpr-1', 'mpr', 'Pharmacology', 'Respiratory', 'Physiological Integrity', 'Moderate', '[\"A\",\"B\",\"C\"]', '[\"A\",\"B\",\"D\",\"E\"]', 0, 0.25, 1, 4, 'Opioids such as morphine can depress the respiratory system and cause hypotension and sedation. Monitoring respiratory rate, oxygen saturation, sedation level, and blood pressure are essential nursing assessments. Rapid ambulation is not an immediate priority after opioid administration.', 1, 2, 1, '2026-03-26 07:19:36'),
(121, 45, 1111, 'mmr-3', 'mmr', 'Pediatrics – Fluid & Electrolyte', 'Pediatric Care', 'Critical Nursing Care: Pediatrics', 'Moderate', '{\"Interventions\":[\"Start IV fluids\",\"Offer oral rehydration solution\",\"Check urine output\"],\"Monitoring Parameters\":[\"Monitor weight\",\"Assess skin turgor\"]}', '{\"Interventions\":[\"Start IV fluids\",\"Offer oral rehydration solution\"],\"Monitoring Parameters\":[\"Monitor weight\",\"Assess skin turgor\",\"Check urine output\"]}', 0, 0.6, 3, 5, 'Treatment depends on severity; hydration status must be monitored closely.', 1, 5, 4, '2026-03-26 07:20:15'),
(122, 45, 1112, 'mpr-7', 'mpr', 'Infectious Diseases', 'Respiratory', 'CNC2', 'Basic', '[\"A\",\"B\"]', '[\"A\",\"D\"]', 0, 0, 0, 2, 'Fever and rash are classical symptoms of measles; headache and cough are less specific.', 1, 3, 2, '2026-03-26 07:20:48'),
(123, 45, 1112, 'mpr-5', 'mpr', 'Maternal Health', 'Reproductive', 'Physiological Integrity', 'Hard', '[\"B\",\"D\"]', '[\"A\",\"B\",\"C\",\"E\"]', 0, 0, 0, 4, 'Placental abruption often presents with abdominal pain, vaginal bleeding, uterine tenderness, and decreased fetal movement due to compromised placental circulation. Mild back discomfort alone is not a typical sign.', 2, 5, 8, '2026-03-26 07:20:54'),
(124, 45, 1113, 'mmr-2', 'mmr', 'Adult Health – Infection / Sepsis', 'Adult Health', 'Critical Nursing Care', 'Hard', '{\"Nursing Actions\":[\"Administer IV fluids\",\"Check vital signs frequently\",\"Obtain blood cultures\",\"Provide oxygen therapy\"],\"Monitoring Parameters\":[\"Monitor urine output\"]}', '{\"Nursing Actions\":[\"Administer IV fluids\",\"Obtain blood cultures\",\"Provide oxygen therapy\"],\"Monitoring Parameters\":[\"Check vital signs frequently\",\"Monitor urine output\"]}', 0, 0.6, 3, 5, 'Sepsis management requires early fluids, cultures, oxygen, and close monitoring of vitals and urine output.', 1, 6, 5, '2026-03-26 07:21:22'),
(125, 45, 1114, 'mpr-5', 'mpr', 'Maternal Health', 'Reproductive', 'Physiological Integrity', 'Hard', '[\"A\",\"E\"]', '[\"A\",\"B\",\"C\",\"E\"]', 0, 0.5, 2, 4, 'Placental abruption often presents with abdominal pain, vaginal bleeding, uterine tenderness, and decreased fetal movement due to compromised placental circulation. Mild back discomfort alone is not a typical sign.', 1, 2, 1, '2026-03-26 07:22:06'),
(126, 45, 1114, 'highlight-2', 'highlight', 'Respiratory | Droplet Precautions', '', '', '', '[\"sore throat\",\"nasal discharge\"]', '[\"cough\",\"sore throat\",\"temperature 38.5\\u00b0C\",\"nasal discharge\",\"positive influenza A\"]', 0, 0.4, 2, 5, 'Droplet precautions are used for conditions transmitted through large respiratory droplets, such as influenza.', 2, 5, 10, '2026-03-26 07:22:15'),
(156, 45, 1128, 'dragndrop-6', 'dragndrop', 'Immunology adaptive immune response, pathogens', '', '', '', '[\"adaptive immune response\",\"Hyptehelegical system\"]', '[\"adaptive immune response\",\"pathogens\"]', 0, 0.5, 1, 2, 'Adaptive immune response is carried out by lymphocytes to target and destroy pathogens.', 1, 3, 2, '2026-03-26 07:42:40'),
(157, 45, 1128, 'mpr-8', 'mpr', 'Nutrition', 'Gastrointestinal', 'CNC3', 'Intermediate', '[\"C\"]', '[\"A\",\"C\"]', 0, 0.5, 1, 2, 'Vitamin C helps iron absorption; Vitamin A and D are fat-soluble; B12 is water-soluble.', 2, 2, 7, '2026-03-26 07:42:45'),
(158, 45, 1128, 'mpr-12', 'mpr', 'OB/Maternal', 'Maternal Care', 'CNC7', 'Intermediate', '[\"D\"]', '[\"B\"]', 0, 0, 0, 1, 'Blood pressure monitoring is critical; fundal height helps estimate fetal growth; ultrasound confirms anatomy.', 3, 3, 11, '2026-03-26 07:42:50'),
(160, 45, 1129, 'mpr-9', 'mpr', 'Psychiatric Nursing', 'Mental Health', 'CNC4', 'Advanced', '[\"D\"]', '[\"A\",\"C\"]', 0, 0, 0, 2, 'CBT and Psychoeducation are first-line non-pharmacologic therapies.', 2, 1, 2, '2026-03-26 07:43:52'),
(161, 45, 1129, 'mmr-3', 'mmr', 'Pediatrics – Fluid & Electrolyte', 'Pediatric Care', 'Critical Nursing Care: Pediatrics', 'Moderate', '{\"Interventions\":[\"Start IV fluids\",\"Offer oral rehydration solution\",\"Assess skin turgor\",\"Check urine output\"],\"Monitoring Parameters\":[\"Monitor weight\"]}', '{\"Interventions\":[\"Start IV fluids\",\"Offer oral rehydration solution\"],\"Monitoring Parameters\":[\"Monitor weight\",\"Assess skin turgor\",\"Check urine output\"]}', 0, 0.2, 1, 5, 'Treatment depends on severity; hydration status must be monitored closely.', 3, 7, 10, '2026-03-26 07:44:01'),
(162, 45, 1129, 'highlight-1', 'highlight', 'Pediatrics – Fluid & Electrolyte', 'Pediatric Care', 'Critical Nursing Care: Pediatrics', 'Moderate', '{\"Interventions\":[\"Start IV fluids\",\"Offer oral rehydration solution\",\"Assess skin turgor\",\"Check urine output\"],\"Monitoring Parameters\":[\"Monitor weight\"]}', '{\"Interventions\":[\"Start IV fluids\",\"Offer oral rehydration solution\"],\"Monitoring Parameters\":[\"Monitor weight\",\"Assess skin turgor\",\"Check urine output\"]}', 0, 0.2, 1, 5, 'Treatment depends on severity; hydration status must be monitored closely.', 1, 6, 22, '2026-03-26 07:44:12'),
(163, 45, 1129, 'highlight-2', 'highlight', 'Respiratory | Droplet Precautions', '', '', '', '[\"sore throat\"]', '[\"cough\",\"sore throat\",\"temperature 38.5\\u00b0C\",\"nasal discharge\",\"positive influenza A\"]', 0, 0.2, 1, 5, 'Droplet precautions are used for conditions transmitted through large respiratory droplets, such as influenza.', 4, 2, 38, '2026-03-26 07:44:28'),
(171, 45, 1133, 'bowtie-17', 'bowtie', '', '', '', '', '{\"actions\":[\"Delay IV fluids\",\"Administer prescribed antibiotics\"],\"conditions\":[\"Septic shock\"],\"parameters\":[\"Urine color\",\"Blood pressure\"]}', '{\"actions\":[\"Administer prescribed antibiotics\",\"Monitor vital signs frequently\"],\"conditions\":[\"Septic shock\"],\"parameters\":[\"Blood pressure\",\"Temperature\"]}', 0, 0.6, 3, 5, 'Early antibiotic therapy and close monitoring are critical in sepsis management.', 1, 17, 15, '2026-03-26 07:54:22'),
(175, 45, 1134, 'mmr-5', 'mmr', 'Psychiatric Nursing', 'Mental Health System', 'Psych Nursing Care', 'Moderate', '{\"Interventions\":[\"Encourage social interaction\"],\"Monitoring Parameters\":[\"Provide safety monitoring\"]}', '{\"Interventions\":[\"Encourage social interaction\",\"Teach coping skills\",\"Offer relaxation techniques\"],\"Monitoring Parameters\":[\"Provide safety monitoring\",\"Monitor mood\"]}', 0, 0.4, 2, 5, 'Depression requires engagement and mood monitoring; anxiety requires coping and relaxation strategies.', 1, 4, 3, '2026-03-26 07:55:05'),
(176, 45, 1134, 'highlight-1', 'highlight', 'Psychiatric Nursing', 'Mental Health System', 'Psych Nursing Care', 'Moderate', '{\"Interventions\":[\"Encourage social interaction\"],\"Monitoring Parameters\":[\"Provide safety monitoring\"]}', '{\"Interventions\":[\"Encourage social interaction\",\"Teach coping skills\",\"Offer relaxation techniques\"],\"Monitoring Parameters\":[\"Provide safety monitoring\",\"Monitor mood\"]}', 0, 0.4, 2, 5, 'Depression requires engagement and mood monitoring; anxiety requires coping and relaxation strategies.', 2, 10, 15, '2026-03-26 07:55:16'),
(184, 45, 1135, 'highlight-2', 'highlight', 'Respiratory | Droplet Precautions', '', '', '', '[\"inflamed tonsils\"]', '[\"cough\",\"sore throat\",\"temperature 38.5\\u00b0C\",\"nasal discharge\",\"positive influenza A\"]', 0, 0, 0, 5, 'Droplet precautions are used for conditions transmitted through large respiratory droplets, such as influenza.', 1, 3, 3, '2026-03-26 07:58:49'),
(185, 45, 1135, 'dragndrop-7', 'dragndrop', 'Immunology T, infected', '', '', '', '[\"T\",\"macrophage\"]', '[\"T\",\"infected\"]', 0, 0.5, 1, 2, 'T cells mature in the thymus and play a direct role in destroying infected or abnormal cells.', 2, 10, 15, '2026-03-26 07:59:02'),
(186, 45, 1135, 'mpr-12', 'mpr', 'OB/Maternal', 'Maternal Care', 'CNC7', 'Intermediate', '[\"D\"]', '[\"B\"]', 0, 0, 0, 1, 'Blood pressure monitoring is critical; fundal height helps estimate fetal growth; ultrasound confirms anatomy.', 3, 2, 25, '2026-03-26 07:59:12'),
(201, 45, 1138, 'mpr-12', 'mpr', 'OB/Maternal', 'Maternal Care', 'CNC7', 'Intermediate', '[\"B\"]', '[\"B\"]', 1, 1, 1, 1, 'Blood pressure monitoring is critical; fundal height helps estimate fetal growth; ultrasound confirms anatomy.', 1, 2, 2, '2026-03-26 08:04:16'),
(232, 45, 1142, 'mmr-5', 'mmr', 'Infection Control', 'Respiratory', 'Safe and Effective Care Environment', 'Moderate', '[\"A\",\"B\",\"C\",\"D\"]', '[\"A\",\"C\",\"D\",\"E\"]', 0, 0.5, 2, 4, 'Tuberculosis requires airborne precautions. The client should be placed in a negative pressure room and healthcare workers should use a mask when in close contact. Hand hygiene is essential. Visitors should also use masks. Sterile gloves are not required for routine care.', 3, 15, 34, '2026-03-26 08:12:11'),
(233, 45, 1142, 'mpr-3', 'mpr', 'Infection Control', 'Respiratory', 'Safe and Effective Care Environment', 'Moderate', '[\"A\",\"B\",\"C\",\"D\"]', '[\"A\",\"C\",\"D\",\"E\"]', 0, 0.5, 2, 4, 'Tuberculosis requires airborne precautions. The client should be placed in a negative pressure room and healthcare workers should use a mask when in close contact. Hand hygiene is essential. Visitors should also use masks. Sterile gloves are not required for routine care.', 4, 220, 257, '2026-03-26 08:15:54'),
(234, 45, 1142, 'highlight-3', 'highlight', 'Infection Control', 'Respiratory', 'Safe and Effective Care Environment', 'Moderate', '[\"A\",\"B\",\"C\",\"D\"]', '[\"A\",\"C\",\"D\",\"E\"]', 0, 0.5, 2, 4, 'Tuberculosis requires airborne precautions. The client should be placed in a negative pressure room and healthcare workers should use a mask when in close contact. Hand hygiene is essential. Visitors should also use masks. Sterile gloves are not required for routine care.', 5, 16, 278, '2026-03-26 08:16:15'),
(235, 45, 1142, 'dragndrop-8', 'dragndrop', 'Infection Control', 'Respiratory', 'Safe and Effective Care Environment', 'Moderate', '[\"A\",\"B\",\"C\",\"D\"]', '[\"A\",\"C\",\"D\",\"E\"]', 0, 0.5, 2, 4, 'Tuberculosis requires airborne precautions. The client should be placed in a negative pressure room and healthcare workers should use a mask when in close contact. Hand hygiene is essential. Visitors should also use masks. Sterile gloves are not required for routine care.', 6, 7, 289, '2026-03-26 08:16:26'),
(236, 45, 1142, 'mmr-4', 'mmr', 'Immunology adaptive immune, B', '', '', '', '[\"adaptive immune\",\"innate\"]', '[\"adaptive immune\",\"B\"]', 0, 0.5, 1, 2, 'The adaptive immune response involves B cells producing specific antibodies.', 7, 10, 303, '2026-03-26 08:16:41'),
(258, 45, 1142, 'mpr-2', 'mpr', 'Infection Control', 'Respiratory', 'Safe and Effective Care Environment', 'Moderate', '[\"D\"]', '[\"A\",\"C\",\"D\",\"E\"]', 0, 0.25, 1, 4, 'Tuberculosis requires airborne precautions. The client should be placed in a negative pressure room and healthcare workers should use a mask when in close contact. Hand hygiene is essential. Visitors should also use masks. Sterile gloves are not required for routine care.', 1, 1, 0, '2026-03-26 08:24:04'),
(259, 45, 1142, 'highlight-2', 'highlight', 'Respiratory | Droplet Precautions', '', '', '', '[\"sore throat\"]', '[\"cough\",\"sore throat\",\"temperature 38.5\\u00b0C\",\"nasal discharge\",\"positive influenza A\"]', 0, 0.2, 1, 5, 'Droplet precautions are used for conditions transmitted through large respiratory droplets, such as influenza.', 2, 6, 8, '2026-03-26 08:24:12'),
(260, 45, 1146, 'highlight-1', 'highlight', 'Infection Control | Standard Precautions', '', '', '', '[\"heart rhythm regular with S1 and S2\"]', '[\"lung sounds without adventitious sounds\",\"heart rhythm regular with S1 and S2\",\"capillary refill +2 sec\",\"equal grips\",\"alert and oriented \\u00d74\"]', 0, 0.2, 1, 5, 'These findings do not require any isolation or transmission precautions beyond standard precautions.', 1, 2, 1, '2026-03-26 08:24:40'),
(261, 45, 1146, 'mpr-9', 'mpr', 'Psychiatric Nursing', 'Mental Health', 'CNC4', 'Advanced', '[\"B\"]', '[\"A\",\"C\"]', 0, 0, 0, 2, 'CBT and Psychoeducation are first-line non-pharmacologic therapies.', 2, 4, 7, '2026-03-26 08:24:46'),
(262, 45, 1146, 'dragndrop-8', 'dragndrop', 'Immunology adaptive immune, B', '', '', '', '[\"adaptive immune\",\"macrophage\"]', '[\"adaptive immune\",\"B\"]', 0, 0.5, 1, 2, 'The adaptive immune response involves B cells producing specific antibodies.', 3, 7, 32, '2026-03-26 08:25:10'),
(263, 45, 1148, 'bowtie-18', 'bowtie', '', '', '', '', '{\"actions\":[\"Administer insulin\",\"Restrict fluids\"],\"conditions\":[\"Diabetic ketoacidosis\"],\"parameters\":[\"Serum potassium\",\"Blood glucose\"]}', '{\"actions\":[\"Administer insulin\",\"Start IV fluids\"],\"conditions\":[\"Diabetic ketoacidosis\"],\"parameters\":[\"Blood glucose\",\"Serum potassium\"]}', 0, 0.8, 4, 5, 'Insulin and fluids correct hyperglycemia and dehydration. Electrolytes must be closely monitored.', 1, 8, 6, '2026-03-26 08:26:03'),
(264, 45, 1147, 'dragndrop-8', 'dragndrop', 'Immunology adaptive immune, B', '', '', '', '[\"T\",\"macrophage\"]', '[\"adaptive immune\",\"B\"]', 0, 0, 0, 2, 'The adaptive immune response involves B cells producing specific antibodies.', 1, 20, 19, '2026-03-26 08:26:05'),
(265, 45, 1148, 'highlight-2', 'highlight', 'Respiratory | Droplet Precautions', '', '', '', '[\"sore throat\",\"inflamed tonsils\"]', '[\"cough\",\"sore throat\",\"temperature 38.5\\u00b0C\",\"nasal discharge\",\"positive influenza A\"]', 0, 0, 0, 5, 'Droplet precautions are used for conditions transmitted through large respiratory droplets, such as influenza.', 2, 4, 12, '2026-03-26 08:26:09'),
(266, 45, 1148, 'dragndrop-6', 'dragndrop', 'Immunology adaptive immune response, pathogens', '', '', '', '[\"pathogens\",\"Immune\"]', '[\"adaptive immune response\",\"pathogens\"]', 0, 0, 0, 2, 'Adaptive immune response is carried out by lymphocytes to target and destroy pathogens.', 3, 6, 20, '2026-03-26 08:26:16'),
(267, 45, 1148, 'mmr-6', 'mmr', 'Nutrition / Miscellaneous', 'Dietary System', 'Nutrition Care', 'Easy', '{\"Dietary Interventions\":[\"Recommend low-sodium diet\"],\"Monitoring Parameters\":[\"Encourage balanced meals\",\"Encourage fluid intake\"]}', '{\"Dietary Interventions\":[\"Monitor blood sugar\",\"Recommend low-sodium diet\",\"Limit sugar intake\",\"Encourage balanced meals\"],\"Monitoring Parameters\":[\"Encourage fluid intake\"]}', 0, 0.2, 1, 5, 'Nutrition plans differ per condition; monitoring ensures effectiveness.', 4, 3, 24, '2026-03-26 08:26:21'),
(268, 45, 1149, 'mmr-2', 'mmr', 'Adult Health – Infection / Sepsis', 'Adult Health', 'Critical Nursing Care', 'Hard', '{\"Nursing Actions\":[\"Administer IV fluids\",\"Obtain blood cultures\",\"Provide oxygen therapy\"],\"Monitoring Parameters\":[\"Check vital signs frequently\",\"Monitor urine output\"]}', '{\"Nursing Actions\":[\"Administer IV fluids\",\"Obtain blood cultures\",\"Provide oxygen therapy\"],\"Monitoring Parameters\":[\"Check vital signs frequently\",\"Monitor urine output\"]}', 1, 1, 5, 5, 'Sepsis management requires early fluids, cultures, oxygen, and close monitoring of vitals and urine output.', 1, 5, 4, '2026-03-26 08:26:37'),
(269, 45, 1149, 'dragndrop-9', 'dragndrop', 'Immunology mast, histamine', '', '', '', '[\"mast\",\"histamine\"]', '[\"mast\",\"histamine\"]', 0, 1, 2, 2, 'Mast cells release histamine, leading to inflammation and allergic manifestations.', 2, 6, 11, '2026-03-26 08:26:44'),
(271, 45, 1149, 'highlight-2', 'highlight', 'Respiratory | Droplet Precautions', '', '', '', '[\"cough\",\"sore throat\",\"fatigue\",\"nasal discharge\",\"inflamed tonsils\"]', '[\"cough\",\"sore throat\",\"temperature 38.5\\u00b0C\",\"nasal discharge\",\"positive influenza A\"]', 0, 0.2, 1, 5, 'Droplet precautions are used for conditions transmitted through large respiratory droplets, such as influenza.', 3, 11, 24, '2026-03-26 08:26:56'),
(279, 45, 1151, 'mmr-5', 'mmr', 'Psychiatric Nursing', 'Mental Health System', 'Psych Nursing Care', 'Moderate', '{\"Interventions\":[\"Encourage social interaction\",\"Teach coping skills\",\"Provide safety monitoring\",\"Offer relaxation techniques\"],\"Monitoring Parameters\":[\"Monitor mood\"]}', '{\"Interventions\":[\"Encourage social interaction\",\"Teach coping skills\",\"Offer relaxation techniques\"],\"Monitoring Parameters\":[\"Provide safety monitoring\",\"Monitor mood\"]}', 0, 0.6, 3, 5, 'Depression requires engagement and mood monitoring; anxiety requires coping and relaxation strategies.', 1, 5, 4, '2026-03-26 08:27:29'),
(282, 45, 1152, 'bowtie-18', 'bowtie', '', '', '', '', '{\"actions\":[\"Restrict fluids\",\"Administer insulin\"],\"conditions\":[\"Diabetic ketoacidosis\"],\"parameters\":[\"Blood glucose\",\"Bowel sounds\"]}', '{\"actions\":[\"Administer insulin\",\"Start IV fluids\"],\"conditions\":[\"Diabetic ketoacidosis\"],\"parameters\":[\"Blood glucose\",\"Serum potassium\"]}', 0, 0.6, 3, 5, 'Insulin and fluids correct hyperglycemia and dehydration. Electrolytes must be closely monitored.', 1, 7, 6, '2026-03-26 08:27:58'),
(283, 45, 1152, 'highlight-2', 'highlight', 'Respiratory | Droplet Precautions', '', '', '', '[\"nasal discharge\",\"inflamed tonsils\",\"cervical lymphadenopathy\",\"sore throat\",\"cough\"]', '[\"cough\",\"sore throat\",\"temperature 38.5\\u00b0C\",\"nasal discharge\",\"positive influenza A\"]', 0, 0.2, 1, 5, 'Droplet precautions are used for conditions transmitted through large respiratory droplets, such as influenza.', 2, 8, 15, '2026-03-26 08:28:07'),
(284, 45, 1154, 'dragndrop-8', 'dragndrop', 'Immunology adaptive immune, B', '', '', '', '[\"macrophage\",\"innate\"]', '[\"adaptive immune\",\"B\"]', 0, 0, 0, 2, 'The adaptive immune response involves B cells producing specific antibodies.', 1, 3, 2, '2026-03-26 08:28:08'),
(285, 45, 1152, 'mmr-3', 'mmr', 'Pediatrics – Fluid & Electrolyte', 'Pediatric Care', 'Critical Nursing Care: Pediatrics', 'Moderate', '{\"Interventions\":[\"Start IV fluids\",\"Offer oral rehydration solution\",\"Monitor weight\",\"Assess skin turgor\"],\"Monitoring Parameters\":[\"Check urine output\"]}', '{\"Interventions\":[\"Start IV fluids\",\"Offer oral rehydration solution\"],\"Monitoring Parameters\":[\"Monitor weight\",\"Assess skin turgor\",\"Check urine output\"]}', 0, 0.2, 1, 5, 'Treatment depends on severity; hydration status must be monitored closely.', 3, 5, 23, '2026-03-26 08:28:15'),
(286, 45, 1152, 'mmr-4', 'mmr', 'OB/Maternal Nursing', 'OB Nursing System', 'Labor & Delivery Care', 'Hard', '{\"Interventions\":[\"Encourage ambulation\",\"Provide pain relief\",\"Support breathing techniques\"],\"Monitoring Parameters\":[\"Monitor contractions\",\"Assess cervical dilation\"]}', '{\"Interventions\":[\"Encourage ambulation\",\"Provide pain relief\",\"Support breathing techniques\"],\"Monitoring Parameters\":[\"Monitor contractions\",\"Assess cervical dilation\"]}', 1, 1, 5, 5, 'Early labor focuses on comfort and monitoring; active labor requires more interventions.', 4, 9, 46, '2026-03-26 08:28:38'),
(287, 45, 1155, 'dragndrop-6', 'dragndrop', 'Immunology adaptive immune response, pathogens', '', '', '', '[\"pathogens\",\"adaptive immune response\"]', '[\"adaptive immune response\",\"pathogens\"]', 0, 0, 0, 2, 'Adaptive immune response is carried out by lymphocytes to target and destroy pathogens.', 1, 4, 3, '2026-03-26 08:29:31'),
(288, 45, 1155, 'highlight-1', 'highlight', 'Infection Control | Standard Precautions', '', '', '', '[\"heart rhythm regular with S1 and S2\"]', '[\"lung sounds without adventitious sounds\",\"heart rhythm regular with S1 and S2\",\"capillary refill +2 sec\",\"equal grips\",\"alert and oriented \\u00d74\"]', 0, 0.2, 1, 5, 'These findings do not require any isolation or transmission precautions beyond standard precautions.', 2, 2, 7, '2026-03-26 08:29:35'),
(301, 45, 1158, 'dragndrop-10', 'dragndrop', 'Immunology memory, pathogen', '', '', '', '[\"memory\",\"pathogen\"]', '[\"memory\",\"pathogen\"]', 0, 1, 2, 2, 'Memory cells provide long-term immunity by remembering pathogens encountered before.', 3, 9, 18, '2026-03-26 08:38:44'),
(302, 45, 1158, 'mmr-4', 'mmr', 'OB/Maternal Nursing', 'OB Nursing System', 'Labor & Delivery Care', 'Hard', '{\"Interventions\":[\"Encourage ambulation\",\"Monitor contractions\",\"Provide pain relief\",\"Assess cervical dilation\"],\"Monitoring Parameters\":[\"Support breathing techniques\"]}', '{\"Interventions\":[\"Encourage ambulation\",\"Provide pain relief\",\"Support breathing techniques\"],\"Monitoring Parameters\":[\"Monitor contractions\",\"Assess cervical dilation\"]}', 0, 0, 0, 5, 'Early labor focuses on comfort and monitoring; active labor requires more interventions.', 4, 4, 23, '2026-03-26 08:38:49'),
(303, 45, 1158, 'mpr-4', 'mpr', 'Pediatrics', 'Neurological', 'Health Promotion and Maintenance', 'Moderate', '[\"A\"]', '[\"A\",\"B\",\"C\",\"E\"]', 0, 0.25, 1, 4, 'Signs of increased intracranial pressure in infants include bulging fontanelle, high-pitched cry, vomiting, and poor feeding. Hypothermia is not a classic sign; fever may occur depending on the cause.', 5, 2, 80, '2026-03-26 08:39:45'),
(304, 45, 1158, 'mpr-9', 'mpr', 'Psychiatric Nursing', 'Mental Health', 'CNC4', 'Advanced', '[\"A\",\"B\"]', '[\"A\",\"C\"]', 0, 0, 0, 2, 'CBT and Psychoeducation are first-line non-pharmacologic therapies.', 1, 5, 4, '2026-03-26 08:41:54'),
(305, 45, 1158, 'highlight-3', 'highlight', 'Dermatology | Contact Precautions', '', '', '', '[\"honey-colored crusts\"]', '[\"rash on right forearm\",\"honey-colored crusts\",\"diagnosis Impetigo\"]', 0, 0.33, 1, 3, 'Impetigo is a superficial bacterial infection that requires contact precautions because it spreads through direct contact.', 2, 4, 9, '2026-03-26 08:42:00'),
(316, 45, 1160, 'highlight-1', 'highlight', 'Infection Control | Standard Precautions', '', '', '', '[\"heart rhythm regular with S1 and S2\"]', '[\"lung sounds without adventitious sounds\",\"heart rhythm regular with S1 and S2\",\"capillary refill +2 sec\",\"equal grips\",\"alert and oriented \\u00d74\"]', 0, 0.2, 1, 5, 'These findings do not require any isolation or transmission precautions beyond standard precautions.', 1, 4, 4, '2026-03-26 08:52:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `temporary_exam_result`
--
ALTER TABLE `temporary_exam_result`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `temporary_exam_result`
--
ALTER TABLE `temporary_exam_result`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=361;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
