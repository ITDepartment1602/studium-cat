<?php
include '../../../config.php';

//quiz start
if(@$_GET['q']== 'quiz' && @$_GET['step']== 2) {
$eid=@$_GET['eid'];
$sn=@$_GET['n'];
$ans=$_POST['ans'];
$qid=@$_GET['qid'];
$user_id=@$_GET['id'];
$kilanlan=@$_GET['kilanlan'];
$q=mysqli_query($con,"SELECT * FROM question WHERE correctans='$qid' AND topic='$kilanlan' " );
while($row=mysqli_fetch_array($q) )
{
$ansid=$row['correctans'];
}
if($ans == $ansid)
{
$q=mysqli_query($con,"SELECT * FROM question WHERE topics1='$eid' AND topic='$kilanlan' " );
while($row=mysqli_fetch_array($q) )
{
$sahi=$row['correctans'];
}
if($sn == 1)
{
$q=mysqli_query($con,"INSERT INTO exam_mode VALUES('$user_id','$eid','$kilanlan' ,'0','0','0','0',NOW())")or die('Error');
}
$q=mysqli_query($con,"SELECT * FROM exam_mode WHERE eid='$eid' AND email='$user_id' AND kilanlan='$kilanlan' ")or die('Error115');

while($row=mysqli_fetch_array($q) )
{
$s=$row['score'];
$r=$row['sahi'];
}
$r++;
$s=$s+$sahi;
$q=mysqli_query($con,"UPDATE `exam_mode` SET `score`=$s,`level`=$sn,`sahi`=$r, date= NOW()  WHERE  email = '$user_id' AND eid = '$eid' AND kilanlan='$kilanlan'")or die('Error124');

}
else
{
$q=mysqli_query($con,"SELECT * FROM question WHERE topics1='$eid' AND topic='$kilanlan' " )or die('Error129');

while($row=mysqli_fetch_array($q) )
{
$wrong=$row['wrong'];
}
if($sn == 1)
{
$q=mysqli_query($con,"INSERT INTO exam_mode VALUES('$user_id','$eid','$kilanlan' ,'0','0','0','0',NOW() )")or die('Error137');
}
$q=mysqli_query($con,"SELECT * FROM exam_mode WHERE eid='$eid' AND email='$user_id' AND kilanlan='$kilanlan' " )or die('Error139');
while($row=mysqli_fetch_array($q) )
{
$s=$row['score'];
$w=$row['wrong'];
}
$w++;
$s=$s-$wrong;
$q=mysqli_query($con,"UPDATE `exam_mode` SET `score`=$s,`level`=$sn,`wrong`=$w, date=NOW() WHERE  email = '$user_id' AND eid = '$eid' AND kilanlan='$kilanlan'")or die('Error147');
}
{
$sn++;
}
}
?>
<!DOCTYPE html>
   <html oncontextmenu="return false" onselectstart="return false" ondragstart="return false">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

      <!--=============== REMIXICONS ===============-->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
      <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
      <!--=============== CSS ===============-->
      <link rel="stylesheet" href="../css/start.css">
      <link rel="stylesheet" href="../css/feedback.css">

      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

      <script type="text/javascript">document.onkeydown = function (e){ return false; }</script>

    <script type="text/javascript">
    document.onmousedown = disableRightclick;
    var message = "<i class='fa fa-exclamation-circle' style='font-size: 20px'></i>&nbsp;<a style='top: 10%'>Warning: Right Click is Disabled</a>";
        function disableRightclick(evt){
        if(evt.button == 2){
        alert(message);
      return false;
      }
    }
  </script>

      <!--=============== CSS ===============-->

    <title>studium</title>
    <link rel="shortcut icon" type="text/css" href="../../../img/logo1.svg">
   </head>
   <body>
  <!--<div id="pre-loader"></div> -->
<div class="qn">Question: 2 out of 150</div>
<div class="containerab">
<div class="navbaraba">
&nbsp;&nbsp;&nbsp;&nbsp;

<p style="font-size: 17px; width: 100%; margin-top: 15px"></p>
<!--==================== Timer ====================-->


<?php require("../timer.php"); ?>
&nbsp;
</div>
</div>
         <!--==================== HEADER ====================-->
          <div class="containerab">

         <!--==================== Content ====================-->
         <div class="subContainerab">
             <div class="main_containerab">
                <div class="contentab">
                    <div class="content3ab">
                    <div class="sidebar-link">
                     <?php include'../feedback.php'; ?>

                    <?php include'../calculator.php'; ?>

                     <a style="cursor: pointer;">
                        <i class="fa fa-arrows-alt" onclick="openFullscreen();" title="Enter Fullscreen"></i>
                     </a>

                     <a class="sidebar-link" style="cursor: pointer;">
                        <i class="fa fa-times-circle" onclick="closeFullscreen();" title="Exit Fullscreen"></i>
                     </a>

                     <?php include'../mynotes.php'; ?>

                     <a href="../../../img/userguide.mp4" target="_blank" class="sidebar-link" style="cursor: pointer; text-decoration:none; color: white;">
                        <i class="fa fa-question-circle" title="User Guide"></i>
                    </a>
                    </div>
                    </div>
         <!--==================== Content Left ====================-->
                    <div class="content1ab">
                     <?php
                     include '../../../config.php';
                     $kilanlan=$_GET['kilanlan'];

                         $sql="select * from question where topic='$kilanlan' ORDER BY RAND() LIMIT 1";
                         $result=mysqli_query($con,$sql);

                         while ($row=mysqli_fetch_array($result))
                          {

                     ?>
<form action="question3.php?q=quiz&step=2&eid=<?php echo $row['topics1']; ?>&n=2&id=<?php echo $_GET['id'] ?>&qid=<?php echo $row['correctans']; ?>&kilanlan=<?php echo $row['topic'] ?>&qq=<?php echo $row['id'] ?>" method='POST' enctype="multipart/form-data">
                      <br>
                     <b>Question 2</b><br><br>

                     <?php echo $row['question'] ?><br><br>

                     <input type="radio" name="ans" value="1" required> <?php echo $row['choiceA'] ?><br><br>
                     <input type="radio" name="ans" value="2" required> <?php echo $row['choiceB'] ?><br><br>
                     <input type="radio" name="ans" value="3" required> <?php echo $row['choiceC'] ?><br><br>
                     <input type="radio" name="ans" value="4" required> <?php echo $row['choiceD'] ?><br><br><br><br>
                     <button class="question button1" type="submit" name="question">Submit</button>
                     </form>
                     </div>
                       <?php } ?>
         <!--==================== Content Right ====================-->
                    <div class="content2ab">

                     </div>

         <!--==================== Content Footer ====================-->
                    <div class="footerab">
<div class="footerac">
<?php
   $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$id'") or die('query failed');
   if(mysqli_num_rows($select) > 0){
      $fetch = mysqli_fetch_assoc($select);
   }
?>
<form method="POST" action="../quiz.php?bundle_name=<?php echo $fetch['bundle_name']; ?>" onsubmit="return submitForm(this);">
    <input class="question button3" type="submit" value="End"/>
</form>
</div>


<a data-toggle="modal" data-target="#qp"><div class="question button2">Question Pages</div></a>
<div class="modal fade" id="qp" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document"  style="max-width:200%; margin-top: -5%;">
    <div class="qpages">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle" style="color: black;"><b>Question Pages</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">

            <!-- Add Note -->
            <div class="col-md-12">
                <div class="card"  style="height: 490px;">
                    <div class="card-body">
                        <div class="data-item">
                            <ul class="list-group" style="color: #747C9E;">
                                <table class="table table-striped data-table">
                                    <thead style="background-color: #5598C6; color: white;">
                                      <tr>
                                        <th>Question Number</th>
                                        <th>Status</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                          <td>Question Number: 1</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 2</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 3</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 4</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 5</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 6</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 7</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 8</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 9</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 10</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 11</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 12</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 13</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 14</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 15</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 16</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 17</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 18</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 19</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 20</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 21</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 22</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 23</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 24</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 25</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 26</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 27</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 28</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 29</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 30</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 31</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 32</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 33</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 34</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 35</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 36</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 37</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 38</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 39</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 40</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>

                                      <tr>
                                          <td>Question Number: 41</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 42</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 43</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 44</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 45</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 46</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 47</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 48</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 49</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 50</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 51</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 52</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 53</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 54</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 55</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 56</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 57</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 58</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 59</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 60</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 61</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 62</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 63</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 64</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 65</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 66</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 67</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 68</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 69</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 70</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 71</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 72</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 73</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 74</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 75</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 76</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 77</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 78</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 79</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 80</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 81</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 82</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 83</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 84</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 85</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 86</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 87</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 88</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 89</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 90</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 91</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 92</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 93</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 94</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 95</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 96</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 97</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 98</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 99</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 100</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 101</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 102</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 103</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 104</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 105</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 106</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 107</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 108</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 109</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 110</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr><tr>
                                          <td>Question Number: 111</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 112</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>

                                      <tr>
                                          <td>Question Number: 113</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 114</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 115</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 116</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 117</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 118</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 119</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 120</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 121</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 122</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 123</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 124</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 125</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 126</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 127</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 128</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>

                                      <tr>
                                          <td>Question Number: 129</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 130</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 131</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 132</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 133</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 134</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 135</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 136</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 137</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 138</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 139</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 140</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 141</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 142</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 143</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 144</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 145</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 146</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 147</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 148</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 149</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                      <tr>
                                          <td>Question Number: 150</td>
                                          <td style="color: red;">Unseen</td>
                                      </tr>
                                    </tbody>
                                </table>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</div>


<form method="POST" action="question3.php?id=<?php echo $_GET['id']; ?>&kilanlan=<?php echo $_GET['kilanlan']; ?>">
  <button class="question button2">Next</button>
</form>
                  </div>
                </div>
            </div>
        </div>
    </div>




         <!--==================== Java Script ====================-->
         <script src="../assets/js/loading.js"></script>
      <script src="../assets/js/main.js"></script>
      <script src="../assets/js/script.js"></script>
      <script src="../assets/js/sweet.min.js"></script>
         <!--==================== Java Script Calculator====================-->
      <script type="text/javascript">
         var calculator_btn = document.querySelector(".calculator_btn");
         var wrapper = document.querySelector(".wrapper");
         var close_btns = document.querySelectorAll(".close_btn");

         calculator_btn.addEventListener("click", function () {
           wrapper.classList.add("active");
         });

         close_btns.forEach(function (btn) {
           btn.addEventListener("click", function () {
             wrapper.classList.remove("active");
           });
         });

      </script>
         <!--==================== Java Script Note====================-->

<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal
btn.onclick = function() {
  modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>
         <!--==================== Java Script Back====================-->

<script>
    function submitForm(form) {
        swal({
            title: "Are you sure?",
            text: "Your data will be lost",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then(function (isOkay) {
            if (isOkay) {
                form.submit();
            }
        });
        return false;
    }
</script>

         <!--==================== Java Script Fullscreen====================-->

<script>
var elem = document.documentElement;
function openFullscreen() {
  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) { /* Safari */
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) { /* IE11 */
    elem.msRequestFullscreen();
  }
}

function closeFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.webkitExitFullscreen) { /* Safari */
    document.webkitExitFullscreen();
  } else if (document.msExitFullscreen) { /* IE11 */
    document.msExitFullscreen();
  }
}
</script>

<script src="../assets/js/disable.js"></script>
   </body>
</html>
