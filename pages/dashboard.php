<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EBC-Service</title>
</head>
<body>

<li class="nav-item">
    <a href="../php/logout.php" class="nav-link">
    <i class="nav-icon text-danger fa fa-power-off"></i>
        <p>
            Logout
        </p>
    </a>
</li>

<script src="../plugins/jquery/jquery-3.7.1.min.js"></script>
<script src="../scripts/fetch.js"> </script>
<script>
    
    $(document).ready(function () {
        fetchAllData();    
    });

</script>

</body>
</html>