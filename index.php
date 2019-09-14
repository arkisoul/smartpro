<?php
  require_once 'config/database.php';

  $DB = new Database();

  $DB->PrintBaseUrl();

?>
<!DOCTYPE html>
<html>
<head>
	<title>SmartPro - Home</title>
</head>
<body>
  <?php echo "Hello World!"; ?>
</body>
</html>
