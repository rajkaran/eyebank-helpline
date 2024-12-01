<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Replicating Information</title>
</head>

<body>
<form id="form1" name="form1" method="get" action="netram_helpline.php">
	<h1><strong><center>
    	Input Contact Details
	</center></strong></h1>
	<p>&nbsp;</p>
  <div align="center">
  <p>
    <label for="mb">Mobile Phone Number</label>
    <input type="text" name="mb" id="mb" />
  </p>
  <p style="padding-right:60px">
    <label for="type">Type of Contact</label>
    <select name="type" size="1" id="type">
      <option value="0">Sent Message</option>
      <option value="1">Calling</option>
    </select>
  </p>
  <p>
    <label for="ms">Content of Message<br />
    </label>
    <textarea name="ms" id="ms" cols="45" rows="5"></textarea>
  </p>
  <p>
    <input type="submit" name="submit" id="submit" value="Submit" />
  </p>
  </div>
</form>
</body>
</html>