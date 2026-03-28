<!DOCTYPE html> 
<html> 
<style> /* Full-width input fields */ 

/* Set a style for all buttons */ 

.cancelbtn { 
  width: auto; 
  padding: 10px 18px; 
  background-color: #4682b4; 
} 
/* Center the image and position the close button */ 
.imgcontainer { 
  text-align: center; 
  margin: 24px 0 12px 0; 
  position: relative; 
} 

/* The Modal (background) */ 
.modal { 
  display: none; 
  position: fixed; 
  z-index: 1; 
  left: 0; 
  top: 0; 
  width: 100%; 
  height: 100%; 
  overflow: auto; 
  background-color: rgb(0,0,0); 
  background-color: rgba(0,0,0,0.4);
  padding-top: 60px; 
} 
.modal-contenta { 
  background-color: #fefefe; 
  margin: 5% auto 15% auto; 
  border: 1px solid #888; 
  width: 20%; 
} 
/* The Close Button (x) */ 
.closea { 
  position: absolute; 
  right: 15px; 
  top: 0;
  margin-top: -30px; 
  color: gray; 
  font-size: 25px; 
  font-weight: bold; 
} 
.closea:hover, .closea:focus { 
  color: black; 
  cursor: pointer; 
}

 /* Add Zoom Animation */ 
.animate { 
-webkit-animation: animatezoom 0.6s; 
animation: animatezoom 0.6s; 
} 
 
@keyframes animatezoom { 
from {
  transform: scale(0)
  } 
to {
  transform: scale(1)
  } 
} 

/* Change styles for span and cancel button on extra small screens */ 
@media(max-width: 768px){ 
.modal-contenta{
  width: 90%;
  margin-top: 100px;
}
} 
</style> 
 <body> 

  <a onclick="document. getElementById('id01') .style.display='block'" style=" cursor: pointer;" class="sidebar-link" title="Calculator">
    <i class="fa fa-calculator" style="cursor: pointer;"></i>
  </a> 
  <div id="id01" class="modal"> 
    <div class="modal-contenta animate"> 
      <div class="imgcontainer"> 
        <div style="color:black;"><b>Calculator</b></div>
        <span onclick="document .getElementById('id01') .style.display='none'" class="closea" title="Close"> &times;</span> 
      </div> 
        <br>
                 <div class="project">
                  <div class="container">
                    <div class="body">
                      <div class="project_name_wrap">
                        <input type="text" class="display" disabled />
                      </div>
                      <div class="project_type_wrap">
                  <div class="buttonsac">
                    <button class="operatorac" data-value="AC">AC</button>
                    <button class="operatorac" data-value="DEL">DEL</button>
                    <button class="operatorac" data-value="%">%</button>
                    <button class="operatorac" data-value="/">/</button>

                    <button data-value="7">7</button>
                    <button data-value="8">8</button>
                    <button data-value="9">9</button>
                    <button class="operatorac" data-value="*">*</button>

                    <button data-value="4">4</button>
                    <button data-value="5">5</button>
                    <button data-value="6">6</button>
                    <button class="operatorac" data-value="-">-</button>

                    <button data-value="1">1</button>
                    <button data-value="2">2</button>
                    <button data-value="3">3</button>
                    <button class="operatorac" data-value="+">+</button>

                    <button data-value="0">0</button>
                    <button data-value="00">00</button>
                    <button data-value=".">.</button>
                    <button class="operatorac" data-value="=">=</button>
                  </div>
                      </div>
                    </div>
                  </div>
                </div>  
        </div> 
      </div> 

  <script>  
  var modal = document.getElementById ('id01'); 
  window.onclick = function(event) { 
  if (event.target == modal) { 
  modal.style.display = "none"; 
    } 
  }    
  </script> 
</body> 
</html>