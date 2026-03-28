<?php 
include '../../config.php';

class count
{
	public $conn;
	public $user_details;
	public $course_count=0;
	public $video_count=0;
	public $packege_count=0;
	public $books_count=0;
	public $old_count=0;
	public $latitude_count=0;
	public $faq_list;

	public function __construct()
	{
		global $connQuiz;
		$this->conn = $connQuiz;
	}
// ===================================================================================
	public function show_users()     // function to display users list
	{
		$query="select * from signup";
		$result=$this->conn->query($query);
		
		while($row=$result->fetch_array(MYSQLI_ASSOC))
		{
			$this->user_details[]=$row;
		}
		return $this->user_details;
	}
	
// =================================================================================================================

 	public function user()           //function to display number of inquires
 	{
 		$query="select * from signup";
 		$result=$this->conn->query($query);
 		
 		while($row=$result->fetch_array(MYSQLI_ASSOC))
 		{
 			// $this->user_details[]=
 			$this->course_count++;
 		}
 		// return $this->user_details;
 		print_r($this->course_count);

 	}

 



 // =================================================================================================================


	public function concept()        //function to display number of Proceed
	{
		$query="select * from signup where status ='3'";
 		$result=$this->conn->query($query);
 		
 		while($row=$result->fetch_array(MYSQLI_ASSOC))
 		{
 			// $this->user_details[]=
 			$this->video_count++;
 		}
 		// return $this->user_details;
 		print_r($this->video_count);

	} 


// =================================================================================================================


		public function questions()         //function to display number of Not Proceed
	{ 
		$query="select * from signup where status ='2'";
 		$result=$this->conn->query($query);
 		
 		while($row=$result->fetch_array(MYSQLI_ASSOC))
 		{
 			// $this->user_details[]=
 			$this->packege_count++;
 		}
 		// return $this->user_details;
 		print_r($this->packege_count);

	} 


// =================================================================================================================


		public function bundles()         //function to display number of Pending
	{
		$query="select * from signup where status ='1'";
 		$result=$this->conn->query($query);
 		
 		while($row=$result->fetch_array(MYSQLI_ASSOC))
 		{
 			// $this->user_details[]=
 			$this->books_count++;
 		}
 		// return $this->user_details;
 		print_r($this->books_count);

	} 

}
 ?>