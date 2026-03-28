<?php
include '../../config.php';

class CountTopics
{
    public $conn;
    public $topicsCount = [];

    public function __construct()
    {
        global $con;
        $this->conn = $con;
    }

    public function __destruct()
    {
        // Don't close connection here as it's shared
    }

    public function countTopics()
    {
        // Select quiz database
        global $con;
        mysqli_select_db($con, DB_QUIZ_NAME);
        
        // Define the topics to count
        $topics = [
            'Pain Meds', 'Antepartum', 'Assignment/Delegation', 'Cardiovascular', 'Oncology',
            'Emergency Care', 'Endocrine', 'Nursing Legalities', 'Fluid and Electrolyte',
            'Gastrointestinal/Nutrition', 'Growth and Development', 'Hematology', 'Immunology',
            'Communicable Disease', 'Integumentary', 'Management Concepts', 'Psychiatry',
            'Musculoskeletal', 'Neurology', 'Prioritization', 'Psych Meds', 'Respiratory',
            'Skills/Procedures', 'Genitourinary', 'Eyes/Ears/Nose/Throat', 'Intrapartum',
            'Postpartum', 'Labor and Delivery', 'Drug Computations', 'Culture and Religion',
            'Neonatology', 'End of Life Care', 'Communication'
        ];

        // Prepare and execute the queries for each topic
        foreach ($topics as $topic) {
            $query = "SELECT COUNT(*) as count FROM question WHERE system = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $topic);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            // Store the count in the topicsCount array
            $this->topicsCount[$topic] = $row['count'];
        }
    }

   
}

// Usage Example
$countTopics = new CountTopics();
$countTopics->countTopics();


?>