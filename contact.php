<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$success = '';
$error = '';
$debug_info = '';

// Enable debugging (set to false in production)
$debug_mode = false;

function debug_log($message) {
    global $debug_mode, $debug_info;
    if ($debug_mode) {
        $debug_info .= $message . "<br>";
        error_log($message);
    }
}

// Check if contact_messages table exists
function table_exists($conn, $table_name) {
    $result = $conn->query("SHOW TABLES LIKE '$table_name'");
    return $result->num_rows > 0;
}

// Process contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("Form submitted");
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please try again.';
        debug_log("CSRF validation failed");
    } else {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $subject = sanitize_input($_POST['subject']);
        $message = sanitize_input($_POST['message']);
        
        debug_log("Form data: name=$name, email=$email, subject=$subject");
        
        // Validate input
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
            debug_log("Empty fields detected");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
            debug_log("Invalid email format: $email");
        } else {
            // Check if contact_messages table exists
            if (!table_exists($conn, 'contact_messages')) {
                debug_log("contact_messages table doesn't exist");
                
                // Create the table
                $create_table_sql = "CREATE TABLE IF NOT EXISTS contact_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    subject VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
                    notes TEXT
                )";
                
                if ($conn->query($create_table_sql) === TRUE) {
                    debug_log("contact_messages table created successfully");
                } else {
                    debug_log("Error creating table: " . $conn->error);
                    $error = 'Database setup error. Please contact the administrator.';
                }
            }
            
            if (empty($error)) {
                // Insert message into database
                $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
                
                // Check if prepare was successful
                if ($stmt === false) {
                    debug_log("Prepare statement failed: " . $conn->error);
                    $error = 'Failed to process your message. Please try again later.';
                } else {
                    $stmt->bind_param("ssss", $name, $email, $subject, $message);
                    
                    if ($stmt->execute()) {
                        $success = 'Your message has been sent successfully. We will get back to you soon!';
                        debug_log("Message inserted successfully with ID: " . $stmt->insert_id);
                        
                        // Send email notification (in a real application)
                        // mail('info@electroshop.com', 'New Contact Form Submission: ' . $subject, $message, 'From: ' . $email);
                    } else {
                        $error = 'Failed to send message. Please try again later.';
                        debug_log("Execute failed: " . $stmt->error);
                    }
                    
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ElectroShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        .debug-toggle {
            margin-top: 10px;
            text-align: center;
        }
        .debug-toggle button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .success-message {
            background-color: rgba(0, 184, 148, 0.1);
            color: #00b894;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #00b894;
        }
        .error-message {
            background-color: rgba(214, 48, 49, 0.1);
            color: #d63031;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #d63031;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a> &gt;
            <span>Contact Us</span>
        </div>
        
        <div class="contact-hero">
            <div class="contact-hero-content">
                <h1>Contact Us</h1>
                <p>We'd love to hear from you. Reach out with any questions, feedback, or inquiries.</p>
            </div>
        </div>
        
        <div class="contact-container">
            <div class="contact-info">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Our Location</h3>
                    <p>Masnaa Road<br>Majdal Anjar, Bekaa<br>Lebanon</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Phone Number</h3>
                    <p>Customer Service: +961 78 948 628<br>Technical Support: +961 71 801 825</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Address</h3>
                    <p>General Inquiries: sonysat@hotmail.com<br>Support: khaled.khatib1242005@gmail.com</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Business Hours</h3>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 5:00 PM<br>Sunday: Closed</p>
                </div>

                <div class="social-links">
                    <h3>Connect With Us</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="contact-form-container">
                <h2>Send Us a Message</h2>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="contact.php" method="POST" class="contact-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="6" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
                
                <?php if ($debug_mode && !empty($debug_info)): ?>
                    <div class="debug-toggle">
                        <button onclick="document.getElementById('debug-info').style.display = document.getElementById('debug-info').style.display === 'none' ? 'block' : 'none';">
                            Toggle Debug Info
                        </button>
                    </div>
                    <div id="debug-info" class="debug-info" style="display: none;">
                        <strong>Debug Information:</strong><br>
                        <?php echo $debug_info; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="map-container">
            <h2>Find Us</h2>
            <div class="map">
<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3319.2212025286226!2d35.91780725871494!3d33.70322465144797!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1518d2b205063593%3A0xddaad934e2558fd5!2sPW3C%2B82%2C%20Masnaa!5e0!3m2!1sen!2slb!4v1747843697506!5m2!1sen!2slb" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>            </div>
        </div>
        
        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What are your shipping options?</h3>
                        <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>We offer standard shipping (3-5 business days), express shipping (1-2 business days), and same-day delivery in select areas. Shipping is free for orders over $100.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What is your return policy?</h3>
                        <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>We offer a 30-day return policy for most items. Products must be in their original packaging and in unused condition. Some items, such as custom orders, may not be eligible for return.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Do you offer international shipping?</h3>
                        <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, we ship to most countries worldwide. International shipping rates and delivery times vary by location. Please contact our customer service team for specific information about shipping to your country.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How can I track my order?</h3>
                        <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                    </div>
                    <div class="faq-answer">
                        <p>Once your order ships, you'll receive a tracking number via email. You can also track your order by logging into your account on our website and viewing your order history.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ toggle
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-answer');
                const toggle = item.querySelector('.faq-toggle');
                
                question.addEventListener('click', function() {
                    // Close all other answers
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.querySelector('.faq-answer').style.display = 'none';
                            otherItem.querySelector('.faq-toggle i').className = 'fas fa-plus';
                        }
                    });
                    
                    // Toggle current answer
                    if (answer.style.display === 'block') {
                        answer.style.display = 'none';
                        toggle.innerHTML = '<i class="fas fa-plus"></i>';
                    } else {
                        answer.style.display = 'block';
                        toggle.innerHTML = '<i class="fas fa-minus"></i>';
                    }
                });
            });
        });
    </script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
