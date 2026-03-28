<?php

class CountTopics
{
    public $host = "localhost";
    public $username = "root";
    public $pass = "";
    public $db_name = "quiz";
    public $conn;
    public $topicsCount = [];

    public function __construct()
    {
        $this->conn = new mysqli($this->host, $this->username, $this->pass, $this->db_name);
        if ($this->conn->connect_errno) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function countTopics()
    {
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