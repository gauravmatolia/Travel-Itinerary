<!-- navbar.php -->
<?php
session_start();
echo '<style>
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 3rem;
        padding-bottom: 1rem;
        width: 100%;
    }

    .nav-links li {
        margin: 0 15px;
    }

    .nav-links a {
        color: white;
        text-decoration: none;
        font-size: 1rem;
        font-weight: 100;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .line{
    height: 0.09rem;
    width: 70%;
    background-color: white;
    align-self: center;
}
</style>
'

?>

<nav class='navbar'>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="tours.php">Tours</a></li>
        <li><a href="aboutus.php">About Us</a></li>
        <li><a href="contactus.php">Contact Us</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>
<div class="line"></div>
