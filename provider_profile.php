<?php
require_once 'includes/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    header("Location: customer/select_provider.php?id=$id");
} else {
    header("Location: index.php");
}
exit;
?>
