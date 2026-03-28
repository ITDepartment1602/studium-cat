<?php

class count
{


 	public $host="127.0.0.1";
 	public $username="u436962267_studium";
 	public $pass="Nclexamplified2023";
 	public $db_name="u436962267_studium";
 	public $conn;
	public $user_details;
	public $course_count = 0;
	public $video_count = 0;
	public $packege_count = 0;
	public $books_count = 0;
	public $old_count = 0;
	public $latitude_count = 0;
	public $faq_list;
	public $expire_count = 0;

public $active_count = 0;

	public function __construct()
	{
		$this->conn = new mysqli($this->host, $this->username, $this->pass, $this->db_name);
		if ($this->conn->connect_errno) {
			die("Connection failed: " . $this->conn->connect_error);
		}

		// Set timezone to Philippine time
		$this->conn->query("SET time_zone = '+08:00'");
	}
	// ===================================================================================
	public function show_users()     // function to display users list
	{
		$query = "select * from login";
		$result = $this->conn->query($query);

		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$this->user_details[] = $row;
		}
		return $this->user_details;
	}

	// =================================================================================================================

    public function user() {// ACTIVATED STUDENTS
    date_default_timezone_set('Asia/Manila');
    $current_date = new DateTime();

    $query = "SELECT * FROM login WHERE dateexpired IS NOT NULL AND dateexpired != ''";
    $result = $this->conn->query($query);

    $this->course_count = 0;
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $dateexpired = new DateTime($row['dateexpired']);
        if ($dateexpired > $current_date) {
            $this->course_count++;
        }
    }
    print_r($this->course_count);
}




	// =================================================================================================================


	public function concept()        //TOTAL STUDENTS
	{
		$query = "select * from login";
		$result = $this->conn->query($query);

		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			// $this->user_details[]=
			$this->video_count++;
		}
		// return $this->user_details;
		print_r($this->video_count);

	}


	// =================================================================================================================


	public function questions()         //function to display number of questions
	{
		$query = "select * from question";
		$result = $this->conn->query($query);

		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			// $this->user_details[]=
			$this->packege_count++;
		}
		// return $this->user_details;
		print_r($this->packege_count);

	}


	// =================================================================================================================


public function bundles() {//TOTAL NOT ACTIVATED
    $query = "SELECT COUNT(*) as count 
              FROM login 
              WHERE (dateexpired IS NULL OR dateexpired = '') 
              AND status = 'user'";
    $result = $this->conn->query($query);
    $row = $result->fetch_assoc();

    $this->books_count = $row['count'];
    print_r($this->books_count);
}




public function expired() { //EXPIRED
    date_default_timezone_set('Asia/Manila');
    $current_date = new DateTime();

    $query = "SELECT * FROM login WHERE dateexpired IS NOT NULL AND dateexpired != ''";
    $result = $this->conn->query($query);

    $this->expire_count = 0;
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $dateexpired = new DateTime($row['dateexpired']);
        if ($dateexpired < $current_date) {
            $this->expire_count++;
        }
    }
    print_r($this->expire_count);
}


public function countActiveStudents()
	{
		// Set timezone to Philippine time
		date_default_timezone_set('Asia/Manila');

		// Get the current time
		$currentTime = time();
		// Define the threshold for "Active Now" (10 minutes = 600 seconds)
		$threshold = $currentTime - 600;

		// Query to count active students, excluding those in the 'Admin' group
		$query = "SELECT COUNT(*) as active_count FROM login WHERE lastlogin >= FROM_UNIXTIME($threshold) AND groupname != 'Admin'";
		$result = $this->conn->query($query);

		if ($result) {
			$row = $result->fetch_array(MYSQLI_ASSOC);
			$this->active_count = $row['active_count'];
		} else {
			$this->active_count = 0; // Set to 0 if the query fails
		}

		// Output the count of active students
		print_r($this->active_count);
	}

}
?>