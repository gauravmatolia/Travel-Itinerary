<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Travel Dashboard</title>
  <link rel="stylesheet" href="assets/styles/profile_styles.css"/>
</head>
<body>
  <header>
    <nav>
      <ul class="nav-links">
        <li><a href="index.html">Home</a></li>
        <li><a href="tours.php">Tours</a></li>
        <li><a href="aboutus.html">About Us</a></li>
        <li><a href="contactus.php">Contact Us</a></li>
        <li><a href="profile.php">Profile</a></li>
      </ul>
    </nav>
    <div class="line"></div>
  </header>

  <main>
    <section class="profile-section">
      <img src="assets/Images/user_images/user1.jpeg" alt="Profile Pic" class="profile-pic" />
      <div class="profile-details">
        <h2>Tanmay Chinchore</h2>
        <p>
          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 
          Excepteur sint occaecat cupidatat non proident.
        </p>
      </div>
    </section>

    <section class="bottom-section">
      <!-- Favorite Places -->
      <div class="card fav-places">
        <h3>Favorite places</h3>
        <ol>
          <li><img src="https://via.placeholder.com/40" /> Udaipur</li>
          <li><img src="https://via.placeholder.com/40" /> New Delhi</li>
          <li><img src="https://via.placeholder.com/40" /> Mumbai</li>
        </ol>
      </div>

      <!-- Upcoming Trips -->
      <div class="card upcoming-trips">
        <h3>Upcoming Trips</h3>
        <div class="trip">
          <strong>Haridwar</strong><br/>
          Start Date: 19/03/2025<br/>
          End Date: 25/03/2025
        </div>
        <div class="trip">
          <strong>Kolkata</strong><br/>
          Start Date: 01/04/2025<br/>
          End Date: 10/04/2025
        </div>
      </div>

      <!-- Memories -->
      <div class="card memories">
        <h3>Memories</h3>
        <div class="memory-group">
          <p># Goa Memories</p>
          <div class="memory-images">
            <img src="https://via.placeholder.com/60" />
            <img src="https://via.placeholder.com/60" />
            <img src="https://via.placeholder.com/60" />
          </div>
        </div>
        <div class="memory-group">
          <p># Hyderabad Memories</p>
          <div class="memory-images">
            <img src="https://via.placeholder.com/60" />
            <img src="https://via.placeholder.com/60" />
            <img src="https://via.placeholder.com/60" />
          </div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
