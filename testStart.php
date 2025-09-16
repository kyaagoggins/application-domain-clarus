<?php 

include 'header.php';
echo "Testing Homepage";

?>


<div class="container">
    <button type="button" class="btn" onclick="testFunction()">Test Me!</button>
    <div id='testAlert' class="alert alert-primary" role="alert" style="display: none">
  Test Alert
</div>
</div>

<script>

function testFunction() {
    const alert = document.getElementById('testAlert');
    if (alert.style.display == "none") {
        alert.style.display = "block";
    } else {
        alert.style.display = "none";
    }
}

</script>

<?php include 'footer.php'; ?>