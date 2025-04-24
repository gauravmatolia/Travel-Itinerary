-- 1. Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. States Table
CREATE TABLE states (
    state_id INT AUTO_INCREMENT PRIMARY KEY,
    state_name VARCHAR(100) UNIQUE NOT NULL
);

-- 3. Cities Table
CREATE TABLE cities (
    city_id INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(100) NOT NULL,
    state_id INT NOT NULL,
    FOREIGN KEY (state_id) REFERENCES states(state_id) ON DELETE CASCADE
);

-- 4. Travel Locations Table
CREATE TABLE travel_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(150) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    city_id INT NOT NULL,
    FOREIGN KEY (city_id) REFERENCES cities(city_id) ON DELETE CASCADE
);

-- 5. Tours Table
CREATE TABLE tours (
    tour_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    created_by INT, -- NULL means admin created
    city_id INT,
    state_id INT,
    start_date DATE,
    end_date DATE,
    price DECIMAL(10,2),
    is_public BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(255),
    rating DECIMAL(3,2),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (city_id) REFERENCES cities(city_id),
    FOREIGN KEY (state_id) REFERENCES states(state_id)
);

-- 6. Tour_Locations Table
CREATE TABLE tour_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    location_id INT NOT NULL,
    day_number INT,
    time_slot VARCHAR(50),
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES travel_locations(location_id) ON DELETE CASCADE
);

-- 7. Itineraries Table
CREATE TABLE itineraries (
    itinerary_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    itinerary_name VARCHAR(150),
    state_id INT,
    city_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (state_id) REFERENCES states(state_id),
    FOREIGN KEY (city_id) REFERENCES cities(city_id)
);

-- 8. Itinerary_Locations Table
CREATE TABLE itinerary_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    itinerary_id INT NOT NULL,
    location_id INT NOT NULL,
    day_number INT,
    time_slot VARCHAR(50),
    FOREIGN KEY (itinerary_id) REFERENCES itineraries(itinerary_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES travel_locations(location_id) ON DELETE CASCADE
);

-- 9. Favorites Table
CREATE TABLE favorites (
    fav_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tour_id INT,
    location_id INT,
    added_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES travel_locations(location_id) ON DELETE CASCADE
);

-- 10. Memories Table
CREATE TABLE memories (
    memory_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    caption TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 11. Reviews Table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tour_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE
);

-- 12. Bookings Table
CREATE TABLE bookings (
  booking_id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  tour_id int(11) NOT NULL,
  payment_id int(11) NOT NULL,
  booking_date timestamp NOT NULL DEFAULT current_timestamp(),
  status varchar(20) NOT NULL DEFAULT 'confirmed',
  PRIMARY KEY (booking_id),
  KEY user_id (user_id),
  KEY tour_id (tour_id),
  KEY payment_id (payment_id),
  CONSTRAINT bookings_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT bookings_ibfk_2 FOREIGN KEY (tour_id) REFERENCES tours (tour_id) ON DELETE CASCADE,
  CONSTRAINT bookings_ibfk_3 FOREIGN KEY (payment_id) REFERENCES payments (payment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. Payment table
CREATE TABLE payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  payment_amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(20) NOT NULL,        
  card_details TEXT DEFAULT NULL,             
  upi_id VARCHAR(255) DEFAULT NULL,
  payment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  payment_status VARCHAR(20) NOT NULL DEFAULT 'completed', 
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
