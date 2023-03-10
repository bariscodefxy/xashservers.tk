<html>
	<head>
		<meta charset="utf-8">
		<title>Xash3D Server List</title>
		<link rel="stylesheet" href="<?=$sub?>/assets/css/reset.css">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-with, initial-scale=1.0, user-scalable=no">
		<link rel="stylesheet" type="text/css" href="<?=$sub?>/assets/css/reset.css">
		<link rel="stylesheet" type="text/css" href="<?=$sub?>/assets/css/style.css">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
		<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	</head>
	<body>

		<div class="container bg-white mt-3 mb-3 pt-3 pb-3">
			<h1 class="text-center">Xash3D Server List</h1>
			<div class="servers table-responsive">Loading Servers...</div>
		</div>

		<script type="text/javascript">
			<?php
			require(__DIR__ . "/../assets/js/script.js.php");
			?>
		</script>

	</body>
</html>