<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Khatib Electronics</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/about.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a> &gt;
            <span>About Us</span>
        </div>
        
        <div class="about-hero">
            <div class="about-hero-content">
                <h1>About Khatib Electronics</h1>
                <p>Your trusted partner for quality electronics since 2010</p>
            </div>
        </div>
        
        <div class="about-section">
            <div class="about-content">
                <h2>Our Story</h2>
                <p>KhatibShop was founded in 2010 with a simple mission: to provide high-quality electronics at affordable prices. From humble beginnings, the business has grown into a leading online retailer, offering a wide range of home electronics and appliances to customers nationwide.</p>

                <p>The idea behind KhatibShop was to create a customer-focused store that delivers not just products, but real solutions. Built on a foundation of technical expertise and a shared passion for innovation, a dedicated team came together to turn this vision into reality.</p>

                <p>Over the years, the product selection has expanded to include televisions, refrigerators, washing machines, ovens, and other essential appliances. Despite continued growth, the commitment to quality, affordability, and exceptional customer service remains at the heart of everything we do.</p>
            </div>
            <div class="about-image">
                <img src="images/about/store-front.jpg" alt="ElectroShop Store">
            </div>
        </div>
        
        <div class="about-section reverse">
            <div class="about-content">
                <h2>Our Mission</h2>
                <p>At KhatibShop, our mission is to make reliable, high-quality technology accessible to everyone. We believe that essential electronics and appliances should be affordable and within reach for all households. That’s why we focus on offering great value without compromising on quality.</p>
                <p>We're committed to:</p>
                <ul>
                    <li>Delivering a carefully selected range of trusted home electronics and appliances</li>
                    <li>Providing outstanding customer support at every stage of the shopping experience</li>
                    <li>Embracing innovation to bring our customers the latest in practical technology</li>
                    <li>Promoting sustainability by offering energy-efficient and eco-friendly product choices</li>
                </ul>
            </div>
            <div class="about-image">
                <img src="images/about/team-meeting.jpg" alt="ElectroShop Team">
            </div>
        </div>
        
        <div class="values-section">
            <h2>Our Core Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Quality</h3>
                    <p>We never compromise on the quality of our products. Each item in our inventory is carefully selected and tested to ensure it meets our high standards.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>We believe in honest business practices and transparent communication with our customers. What you see is what you get.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Service</h3>
                    <p>Our customer service team is dedicated to providing a seamless shopping experience and resolving any issues promptly.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Sustainability</h3>
                    <p>We're committed to reducing our environmental footprint and offering eco-friendly alternatives whenever possible.</p>
                </div>
            </div>
        </div>
        
        <div class="team-section">
            <h2>Our Special Customers</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="team-photo">
                        <img src="images/about/mohammed.jpg" alt="John Smith">
                    </div>
                    <h3>Mohammed Asaad</h3>
                    <p class="team-role"></p>
                    <p>Great selection of products and really user-friendly website! Khatib Electronics makes it so easy to find exactly what I need</p>
                </div>
                <div class="team-member">
                    <div class="team-photo">
                        <img src="images/about/elias.jpg" alt="Sarah Johnson">
                    </div>
                    <h3>Elias Al Khatib</h3>
                    <p class="team-role"></p>
                    <p>Excellent customer service and fast delivery! I ordered a laptop and it arrived in perfect condition. Highly recommend shopping here.</p>
                </div>
                <div class="team-member">
                    <div class="team-photo">
                        <img src="images/about/mosaab.jpg" alt="Michael Chen">
                    </div>
                    <h3>Mosaab Abdelrahman</h3>
                    <p class="team-role"></p>
                    <p>I love how organized and clean the layout of the site is. Browsing through different electronics categories is smooth and enjoyable!</p>
                </div>
                <div class="team-member">
                    <div class="team-photo">
                        <img src="images/about/hussein.jpg" alt="Lisa Patel">
                    </div>
                    <h3>Hussein Al Khatib</h3>
                    <p class="team-role"></p>
                    <p>Khatib Electronics has some of the best deals online. Their prices are competitive, and the quality of products is top-notch.</p>
                </div>
            </div>
        </div>
        
        <div class="cta-section">
            <h2>Join the KhatibShop Family</h2>
            <p>Experience the KhatibShop difference today. Browse our extensive catalog, reach out to our friendly team, or visit our store in Tech City.</p>
            <div class="cta-buttons">
                <a href="products.php" class="btn btn-primary">Shop Now</a>
                <a href="contact.php" class="btn btn-secondary">Contact Us</a>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
