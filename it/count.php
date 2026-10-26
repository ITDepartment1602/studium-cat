<?php
include '../config.php';

class count
{
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
		global $con;
		$this->conn = $con;
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

    public function user() { // ACTIVATED — matches students.php?filter=activated
        $query = "SELECT COUNT(*) AS c FROM login
                  WHERE status='user' AND groupname!='Admin'
                    AND dateexpired IS NOT NULL AND dateexpired != '' AND dateexpired > NOW()";
        $row = $this->conn->query($query)->fetch_assoc();
        return (int) $row['c'];
    }




	// =================================================================================================================


	public function concept() // TOTAL STUDENTS — matches students.php?filter=all
	{
		$query = "SELECT COUNT(*) AS c FROM login WHERE status='user' AND groupname!='Admin'";
		$row = $this->conn->query($query)->fetch_assoc();
		return (int) $row['c'];
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
		return $this->packege_count;

	}


	// =================================================================================================================


public function bundles() { // NOT ACTIVATED — matches students.php?filter=notactivated
    $query = "SELECT COUNT(*) AS c FROM login
              WHERE status='user' AND groupname!='Admin'
                AND (dateexpired IS NULL OR dateexpired = '')";
    $row = $this->conn->query($query)->fetch_assoc();
    return (int) $row['c'];
}




public function expired() { // EXPIRED — matches students.php?filter=expired
    $query = "SELECT COUNT(*) AS c FROM login
              WHERE status='user' AND groupname!='Admin'
                AND dateexpired IS NOT NULL AND dateexpired != '' AND dateexpired < NOW()";
    $row = $this->conn->query($query)->fetch_assoc();
    return (int) $row['c'];
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
		$query = "SELECT COUNT(*) as active_count FROM login WHERE status='user' AND groupname != 'Admin' AND lastlogin >= FROM_UNIXTIME($threshold)";
		$result = $this->conn->query($query);

		if ($result) {
			$row = $result->fetch_array(MYSQLI_ASSOC);
			$this->active_count = $row['active_count'];
		} else {
			$this->active_count = 0; // Set to 0 if the query fails
		}

		return $this->active_count;
	}

}
?>