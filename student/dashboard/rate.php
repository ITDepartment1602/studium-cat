<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https:////maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
<style>
* {
  box-sizing: border-box;
}
/* Create three equal columns that floats next to each other */
.column {
  position: absolute;
  float: left;
  margin-top: -25px;
  width: 33.33%;
  padding: 10px;
  height: 100px; /* Should be removed. Only for demonstration */
  text-align: center;
}
/* Clear floats after the columns */
.row2:after {
  content: "";
  display: table;
  clear: both;
}

/* Clear floats after the columns */

.tooltip{ 
  position:relative;
  float:right;
  margin-top: -10px;
}

.tooltip > .tooltip-inner {
  background-color: #0A2558; 
  color:white; 

}

.popOver + .tooltip > .tooltip-arrow {  
  border-top:5px solid #0A2558;
  margin-left: 10px;
}
.progress-bar{
  display: block;
  background: white;
  box-shadow: 0 0 10px white;
}

@media (max-width:868px){
    .popOver + .tooltip > .tooltip-arrow{
        border-top:5px solid #0A2558;
        margin-left: -1490px;
    }


.tooltip > .tooltip-inner {
  margin-left: -1510px;
  width: 50px;

}
}
</style>
</head>
<body>

<h2>Chance of passing</h2>
<br><br>

      <?php
         $con = mysqli_connect('localhost','root','','quiz') or die('connection failed');
          $select = mysqli_query($con, "SELECT sum(sahi) FROM `history` WHERE email = '$user_id'") or die('query failed');
          while ($rows = mysqli_fetch_array($select)) {
      ?>
    <div class="row1">
        <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo $rows['sum(sahi)']; ?>" aria-valuemin="0" aria-valuemax="1190">   
        <span class="popOver" data-toggle="tooltip" data-placement="top" title="<?php echo $rows['sum(sahi)']; ?>"></span>  
        </div>
    </div>
  <?php } ?>

<br>
    <div class="row2">
      <div class="column" style="background-color:#FFA9AB;">
        <h2>Low</h2>
      </div>
      <div class="column" style="background-color:#A1B6FF; margin-left: 33.33%;">
        <h2>Mid</h2>
      </div>
      <div class="column" style="background-color:#BCFFDF; margin-left: 66.66%;">
        <h2>High</h2>
      </div>
    </div>








  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js" charset="utf-8"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js" charset="utf-8"></script>

  <script>
    $(function () { 
  $('[data-toggle="tooltip"]').tooltip({trigger: 'manual'}).tooltip('show');
});  

// $( window ).scroll(function() {   
 // if($( window ).scrollTop() > 10){  // scroll down abit and get the action   
  $(".progress-bar").each(function(){
    each_bar_width = $(this).attr('aria-valuenow');
    $(this).width(each_bar_width + 'px');
  });
       
 //  }  
// });
  </script>
</body>
</html>