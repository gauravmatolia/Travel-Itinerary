Itinerary Page:


HTML Code:

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Travel Itinerary</title>
  <!-- <link rel="stylesheet" href="itinerary_page.css" /> -->
   <style>

/* body {
  margin: 0;
  padding: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-image: url(Tours_page.jpg);
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
  min-height: 100vh;
  color: white;
} */


* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', sans-serif;
}

body {
  background: url(Tours_page.jpg) no-repeat center center/cover;
  color: white;
  min-height: 100vh;
}

/* Navbar */
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 100px 0 100px;
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

/* Line Below Navbar */
.line {
  height: 1.5px;
  width: 90vw; /* Use viewport width to stretch across the entire screen */
  background-color: white;
  margin: 20px 0 0 0;
  position: relative; /* Allows us to offset the line */
  left: 50%;
  right: 50%;
  margin-left: -45vw; /* Offset to counteract the padding and center alignment */
  margin-right: -45vw; /* Offset to counteract the padding and center alignment */
  align-self: center;
}

.itinerary-section {
  padding: 40px 50px;
  color: white;
  font-family: 'Segoe UI', sans-serif;
}

.itinerary-section h2 {
  color: #00ffff;
  font-size: 40px;
  margin-bottom: 30px;
}

.location-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 25px;
  margin-bottom: 50px;
}

.location-box {
  background-color: rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
  text-align: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.location-box img {
  width: 100%;
  height: 160px;
  object-fit: cover;
  display: block;
}

.location-box p {
  margin: 10px 0;
  font-weight: 500;
}

.location-box:hover {
  transform: scale(1.03);
  box-shadow: 0 0 10px #00ffff;
}

/* Highlight active location */
.location-box.active {
  border: 2px solid #00bfff;
}

/* Travel Queue */
.travel-queue {
  position: relative;
  padding-top: 20px;
  border-top: 1px solid white;
}

.travel-queue h3 {
  color: #00ffff;
  margin-bottom: 25px;
  font-size: 30px;
}

.queue-list {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  margin-bottom: 30px;
}

.queue-item {
  background: #8b5e3c;
  color: white;
  padding: 10px 16px;
  border-radius: 30px;
  font-weight: 500;
  position: relative;
}

.queue-item::after {
  content: '';
  width: 10px;
  height: 10px;
  border-right: 8px solid transparent;
  border-left: 8px solid transparent;
  border-top: 8px solid #8b5e3c;
  position: absolute;
  right: -10px;
  top: 50%;
  transform: translateY(-50%) rotate(90deg);
}

.queue-item:last-child::after {
  display: none;
}

/* Proceed Button */
.proceed-btn {
  background-color: #eee0d1;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s ease;
}

.proceed-btn:hover {
  background-color: #d5c1a5;
}
   </style>
</head>
<body>
  <div class="hero">
    <nav class="navbar">
      <div class="nav-search">
        <div class="search-box">
        <input type="text" placeholder="Search">
      </div>
      <img src="search0.svg" alt="search_icon" class="search-icon">
    </div>
      
    
      <ul class="nav-links">
        <li><a href="index.html">Home</a></li>
        <li><a href="aboutus.html">About Us</a></li>
        <li><a href="tours.php">Tours</a></li>
        <li><a href="reviews.php">Reviews</a></li>
        <li><a href="contactus.php">Contact Us</a></li>
        <li><a href="profile.html">Profile</a></li>
      </ul>
    </nav>
    <div class="line"></div> 
    
    <section class="itinerary-section">
      <h2>Creating Itinerary</h2>
      
      <div class="location-grid">
        <div class="location-box"><img src="img1.jpg" alt="Location 1"><p>Location 1</p></div>
        <div class="location-box"><img src="img2.jpg" alt="Location 2"><p>Location 2</p></div>
        <div class="location-box"><img src="img3.jpg" alt="Location 3"><p>Location 3</p></div>
        <div class="location-box"><img src="img4.jpg" alt="Location 4"><p>Location 4</p></div>
        <div class="location-box"><img src="img5.jpg" alt="Location 5"><p>Location 5</p></div>
        <div class="location-box"><img src="img6.jpg" alt="Location 6"><p>Location 6</p></div>
        <div class="location-box"><img src="img7.jpg" alt="Location 7"><p>Location 7</p></div>
        <div class="location-box"><img src="img8.jpg" alt="Location 8"><p>Location 8</p></div>
      </div>
    
      <div class="travel-queue">
        <h3>Your Travel Queue</h3>
        <div class="queue-list">
          <div class="queue-item">Location 1</div>
          <div class="queue-item">Location 2</div>
          <div class="queue-item">Location 3</div>
        </div>
        <button class="proceed-btn">Proceed</button>
      </div>
    </section>
    
</body>
</html>
