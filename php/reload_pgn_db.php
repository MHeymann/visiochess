<?php

$thishash = $_POST['hash'];

function resend_file_from_user($hash) {
	$line = "submit_file(get_file_from_hash('" . $hash . "'), 'php/user_upload.php');";
	echo '<script type="text/javascript">',
		$line,
		'</script>';
}
resend_file_from_user($thishash);
?>
