<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Itinerary</title>
    <link rel="stylesheet" href="assets/styles/aboutUs_styles.css">
</head>
<body>
    <!-- Navigation Header -->
    
    <div class="hero" style="background-image:url('assets/Images/aboutUs_background.jpg'); display:flex; flex-direction:column;">
        <?php include('navbar.php'); ?>
    </div>

    <!-- About Us Section -->
    <section class="about-section" style="background-image: linear-gradient( 135deg, #FFD3A5 10%, #FD6585 100%);">
        <div class="about-content">
            <div class="about-box">
                <h2 class="about-title">About Us</h2>
                <p class="about-text">
                At Travel Itinerary, we believe that every journey should be as memorable as the destination itself. We're here to take the stress out of planning and bring back the joy of travel with expertly curated itineraries tailored just for you.

Founded by a team of passionate travelers and logistics pros, we understand that no two travelers are the same. Whether you're chasing waterfalls in Iceland, exploring the streets of Tokyo, or dreaming of a quiet beach in Bali, our customized itineraries ensure you get the most out of every moment.

From solo travelers and couples to families and corporate groups, we craft each itinerary with care, precision, and insider knowledge to deliver seamless experiences. With Travel Itinerary, you don’t just visit a place—you live it.

Let us plan your next adventure—you just pack your bags.
                </p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="features-container">
            <!-- Safety Feature -->
            <div class="feature">
                <div class="feature-icon">
                    <i class="fa fa-shield"></i>
                </div>
                <h3 class="feature-title">Safety</h3>
                <p class="feature-text">Travel Safely with our 10 years of experience</p>
            </div>

            <!-- Support Feature -->
            <div class="feature">
                <div class="feature-icon">
                    <i class="fa fa-headset"></i>
                </div>
                <h3 class="feature-title">Support</h3>
                <p class="feature-text">24/7 availability for convenient tour</p>
            </div>

            <!-- Value Feature -->
            <div class="feature">
                <div class="feature-icon">
                    <i class="fa fa-dollar-sign"></i>
                </div>
                <h3 class="feature-title">Value</h3>
                <p class="feature-text">Competitive pricing and exclusive deals</p>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery">
        <div class="gallery-container">
            <!-- Gallery Item 1 -->
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1540202404-a2f29016b523?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2566&q=80" alt="Beach sunset" class="gallery-image">
            </div>

            <!-- Gallery Item 2 -->
            <div class="gallery-item">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSWytgcz2m-yOU2cyR_mckkENWYeNxM1HIW1A&s" alt="Infinity pool" class="gallery-image">

            </div>

            <!-- Gallery Item 3 -->
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Beach dining" class="gallery-image">
            </div>
        </div>
    </section>

    <!-- Font Awesome for icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>