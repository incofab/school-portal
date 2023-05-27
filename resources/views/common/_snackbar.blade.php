
<div id="snackbar">Some text some message..</div>
<div id="copy-to-clipboard">
</div>
<!-- <input type="text" class="d-none" /> -->
<script type="text/javascript">
function showSnackBar(msg) {
  // Get the snackbar DIV
  var x = document.getElementById("snackbar");

  x.innerHTML = msg;// | 'Snack bar shown';
  
  // Add the "show" class to DIV
  x.className = "show";

  // After 3 seconds, remove the show class from DIV
  setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
}
function copyToClipboard(textToCopy) {
  /* Get the text field */
  var copyText = document.getElementById("copy-to-clipboard");

  // creating textarea of html
  var input = document.createElement("textarea");
  //adding p tag text to textarea 
  input.value = textToCopy;
  document.body.appendChild(input);
//   input.select();
  /* Select the text field */
  input.select();
  input.setSelectionRange(0, 99999); /* For mobile devices */
  var succeed = document.execCommand("Copy");
  // removing textarea after copy
  input.remove();
//   alert(input.value);







  
//   copyText.value = textToCopy;

//   /* Select the text field */
//   copyText.select();
//   copyText.setSelectionRange(0, 99999); /* For mobile devices */

  /* Copy the text inside the text field */
//   var succeed = document.execCommand("copy");
console.log(succeed, textToCopy);
  showSnackBar('Text Copied');
  /* Alert the copied text */
//   alert("Copied the text: " + copyText.value);
}
</script>
<style>
    /* The snackbar - position it at the bottom and in the middle of the screen */
    #snackbar {
      visibility: hidden; /* Hidden by default. Visible on click */
      min-width: 250px; /* Set a default minimum width */
      margin-left: -125px; /* Divide value of min-width by 2 */
      background-color: #333; /* Black background color */
      color: #fff; /* White text color */
      text-align: center; /* Centered text */
      border-radius: 2px; /* Rounded borders */
      padding: 16px; /* Padding */
      position: fixed; /* Sit on top of the screen */
      z-index: 1; /* Add a z-index if needed */
      left: 50%; /* Center the snackbar */
      bottom: 30px; /* 30px from the bottom */
    }
    
    /* Show the snackbar when clicking on a button (class added with JavaScript) */
    #snackbar.show {
      visibility: visible; /* Show the snackbar */
      /* Add animation: Take 0.5 seconds to fade in and out the snackbar.
      However, delay the fade out process for 2.5 seconds */
      -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
      animation: fadein 0.5s, fadeout 0.5s 2.5s;
    }
    
    /* Animations to fade the snackbar in and out */
    @-webkit-keyframes fadein {
      from {bottom: 0; opacity: 0;}
      to {bottom: 30px; opacity: 1;}
    }
    
    @keyframes fadein {
      from {bottom: 0; opacity: 0;}
      to {bottom: 30px; opacity: 1;}
    }
    
    @-webkit-keyframes fadeout {
      from {bottom: 30px; opacity: 1;}
      to {bottom: 0; opacity: 0;}
    }
    
    @keyframes fadeout {
      from {bottom: 30px; opacity: 1;}
      to {bottom: 0; opacity: 0;}
    }
</style>