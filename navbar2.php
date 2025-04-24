<?php

if (!isset($searchTerm)) {
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
}


echo '<style>
    /* Navbar */
    .navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 100px 10px 100px;
        width: 100%;
        background: transparent;
    }
    
    /* Search Box */
    .search-box {
        display: flex;
        align-items: center;
        background-color: white;
        border-radius: 20px;
        padding: 6px 12px;
        width: 220px;
        border: 2px solid transparent; /* <-- initially no visible border */
        transition: 0.3s ease; /* smooth effect */
    }
    
    /* On hover of the entire search box */
    .search-box:hover {
        border: 2px solid #000000; /* or any color you want */
    }
    
    .search-box input {
        border: none;
        outline: none;
        width: 100%;
        font-size: 16px;
        background: transparent;
    }
    
    
    /* Search Icon - Positioned before the placeholder */
    .search-button{
        all: unset;
        cursor: pointer;
        background: transparent;
        borders: none;
    }
    .search-icon {
        width: 18px;
        height: 18px;
        margin-right: 8px;
    }
    
    .nav-search {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    /* Search Input */
    .search-box input {
        border: none;
        outline: none;
        width: 100%;
        font-size: 16px;
        background: transparent;
    }

    .search-form{
        display: flex;
        flex-direction: row;
        gap: 2px;
    }

    /* Nav Links */
    .nav-links {
        list-style: none;
        display: flex;
        gap: 30px;
    }
    
    .nav-links li a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        font-size: 16px;
        transition: 0.3s ease;
    }
  
    .nav-links li a:hover {
        color: #00ffff;
    }

    .line {
        height: 1.5px;
        width: 90vw; /* Use viewport width to stretch across the entire screen */
        background-color: white;
        position: relative; /* Allows us to offset the line */
        left: 50%;
        right: 50%;
        margin-left: -45vw; /* Offset to counteract the padding and center alignment */
        margin-right: -45vw; /* Offset to counteract the padding and center alignment */
        align-self: end;
  }

</style>'

?>



<nav class="navbar">
    <div class="nav-search">
        <form class="search-form" action="" method="GET">
            <div class="search-box">
                <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($searchTerm); ?>">
            </div>
            <button type="submit" class="search-button">
                <img src="assets/home_images/search0.svg" alt="search_icon" class="search-icon">
            </button>
        </form>
    </div>
        
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="tours.php">Tours</a></li>
        <li><a href="aboutus.php">About Us</a></li>
        <li><a href="contactus.php">Contact Us</a></li>
        <li><a href="reviews.php">Reviews</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>
</nav>
<div class="line"></div> 

