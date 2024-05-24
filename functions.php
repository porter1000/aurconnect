<?php
// functions.php

function ensureLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header("Location: login.php");
        exit;
    }
}

function sanitize_color($color) {
    return preg_match('/^#[a-f0-9]{6}$/i', $color) ? $color : null;
}

// Add other functions as needed
?>
