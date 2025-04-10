<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$seller_id = $_SESSION['id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    $stock = intval($_POST['stock']);

    // Validate inputs
    if (empty($name) || empty($price) || empty($category) || empty($stock)) {
        $error = "All fields are required except description";
    } else if ($stock < 1) {
        $error = "Stock quantity must be at least 1";
    } else {
        // Handle image upload
        $target_dir = "uploads/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image_url = '';
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            if (in_array($_FILES["image"]["type"], $allowed_types)) {
                $file_name = time() . '_' . basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $file_name;
                
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_url = $target_file;
                } else {
                    $error = "Failed to upload image";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG & PNG files are allowed.";
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO products (seller_id, name, description, price, image_url, category, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdssi", $seller_id, $name, $description, $price, $image_url, $category, $stock);

            if ($stmt->execute()) {
                $message = "Product added successfully!";
                // Redirect after 2 seconds
                header("refresh:2;url=seller_dashboard.php");
            } else {
                $error = "Error adding product: " . $conn->error;
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
    <title>Add New Product - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --accent: #1ba23f;
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
            --error: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f0f5f1;
            padding-top: 80px;
        }

        .header {
            background: white;
            padding: 1rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .form-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-title {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        /* Validation styles */
        input.error,
        select.error,
        textarea.error {
            border-color: var(--error);
        }

        input.valid,
        select.valid,
        textarea.valid {
            border-color: var(--primary);
        }

        .error-message {
            color: var(--error);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }

        .error-message.visible {
            display: block;
        }

        /* Style for character count that isn't an error */
        .char-count {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .image-preview {
            width: 200px;
            height: 200px;
            border: 2px dashed #e2e8f0;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .submit-btn {
            background: var(--gradient);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(34, 197, 94, 0.2);
        }

        .submit-btn:disabled {
            background: #cbd5e1;
            transform: none;
            box-shadow: none;
            cursor: not-allowed;
        }

        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #16a34a;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #dc2626;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark);
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .back-btn:hover {
            color: var(--primary);
        }

        /* Add these new styles for the sidebar layout */
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .sidebar {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        .sidebar-menu a.active {
            background: var(--gradient);
            color: white;
        }

        .sidebar-menu i {
            font-size: 1.25rem;
            width: 24px;
        }

        .seller-info {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .seller-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .seller-email {
            color: #64748b;
            font-size: 0.875rem;
        }

        .main-content {
            max-width: 800px;
        }

        .form-card {
            margin-top: 0;
        }

        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                margin-bottom: 2rem;
            }
        }

        /* Add styles for category badges */
        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .vegetables { background: #dcfce7; color: #166534; }
        .fruits { background: #fff7ed; color: #9a3412; }
        .dairy { background: #eff6ff; color: #1e40af; }
        .grains { background: #fef3c7; color: #92400e; }
        .seeds { background: #f3e8ff; color: #6b21a8; }
        
        /* File input styling */
        .custom-file-input {
            position: relative;
            margin-bottom: 10px;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: #f1f5f9;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--dark);
            border: 1px dashed #cbd5e1;
        }
        
        .file-input-label:hover {
            background: #e2e8f0;
        }
        
        input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-name {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">AgroFresh</div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="template.php" style="color: var(--dark); text-decoration: none;">Home</a>
                <a href="logout.php" style="color: #ef4444; text-decoration: none;">Logout</a>
            </div>
        </nav>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="seller-info">
                <div class="seller-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Seller'); ?></div>
                <div class="seller-email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                <div class="category-badge vegetables">Verified Seller</div>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="seller_dashboard.php">
                        <i class="fas fa-chart-line"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="add_product.php" class="active">
                        <i class="fas fa-plus-circle"></i>
                        Add Product
                    </a>
                </li>
                <li>
                    <a href="manage_products.php">
                        <i class="fas fa-box"></i>
                        Manage Products
                    </a>
                </li>
                <li>
                    <a href="seller_orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                    </a>
                </li>
                <li>
                    <a href="seller_profile.php">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                </li>
                <li>
                    <a href="seller_analytics.php">
                        <i class="fas fa-chart-bar"></i>
                        Analytics
                    </a>
                </li>
                <li>
                    <a href="seller_settings.php">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
            </ul>

            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                <a href="seller_help.php" style="display: flex; align-items: center; gap: 0.75rem; color: #64748b; text-decoration: none;">
                    <i class="fas fa-question-circle"></i>
                    Need Help?
                </a>
            </div>
        </aside>

        <main class="main-content">
            <?php if ($message): ?>
                <div class="message success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <h2 class="form-title">Add New Product</h2>
                <form id="addProductForm" action="" method="POST" enctype="multipart/form-data" novalidate>
                    <div class="form-group">
                        <label for="name">Product Name*</label>
                        <input type="text" id="name" name="name" required>
                        <div class="error-message" id="nameError">Product name is required</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                        <div class="error-message" id="descriptionError">Description is too long (max 500 characters)</div>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (₹)*</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                        <div class="error-message" id="priceError">Please enter a valid price greater than 0</div>
                    </div>

                    <div class="form-group">
                        <label for="category">Category*</label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="vegetables">Vegetables</option>
                            <option value="fruits">Fruits</option>
                            <option value="organic-vegetables">Organic Vegetables</option>
                            <option value="organic-fruits">Organic Fruits</option>
                            <option value="dairy">Dairy Products</option>
                            <option value="grains">Grains</option>
                            <option value="seeds">Seeds</option>
                        </select>
                        <div class="error-message" id="categoryError">Please select a category</div>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock Quantity*</label>
                        <input type="number" id="stock" name="stock" min="1" required>
                        <div class="error-message" id="stockError">Please enter a valid stock quantity (minimum 1)</div>
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <div class="custom-file-input">
                            <label for="image" class="file-input-label">
                                <i class="fas fa-upload"></i>
                                Choose a file
                            </label>
                            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                            <div class="file-name" id="fileName">No file chosen</div>
                        </div>
                        <div class="error-message" id="imageError">Please upload a valid image file (JPG, JPEG, PNG)</div>
                        <div class="image-preview" id="imagePreview">
                            <i class="fas fa-image" style="color: #cbd5e1; font-size: 2rem;"></i>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="submit-btn" disabled>Add Product</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Form validation
        const form = document.getElementById('addProductForm');
        const nameInput = document.getElementById('name');
        const descriptionInput = document.getElementById('description');
        const priceInput = document.getElementById('price');
        const categoryInput = document.getElementById('category');
        const stockInput = document.getElementById('stock');
        const imageInput = document.getElementById('image');
        const submitBtn = document.getElementById('submitBtn');
        
        // Error message elements
        const nameError = document.getElementById('nameError');
        const descriptionError = document.getElementById('descriptionError');
        const priceError = document.getElementById('priceError');
        const categoryError = document.getElementById('categoryError');
        const stockError = document.getElementById('stockError');
        const imageError = document.getElementById('imageError');
        const fileName = document.getElementById('fileName');
        
        // Validation flags
        let nameValid = false;
        let descriptionValid = true; // Optional field but needs to be under limit
        let priceValid = false;
        let categoryValid = false;
        let stockValid = false;
        let imageValid = true; // Optional field
        
        // Validation functions
        function validateName() {
            if (nameInput.value.trim() === '') {
                nameInput.classList.add('error');
                nameInput.classList.remove('valid');
                nameError.classList.add('visible');
                nameValid = false;
            } else {
                nameInput.classList.remove('error');
                nameInput.classList.add('valid');
                nameError.classList.remove('visible');
                nameValid = true;
            }
            updateSubmitButton();
        }
        
        function validateDescription() {
            // Real-time character count validation
            const currentLength = descriptionInput.value.length;
            const maxLength = 500;
            
            if (currentLength > maxLength) {
                descriptionInput.classList.add('error');
                descriptionInput.classList.remove('valid');
                descriptionError.textContent = `Description is too long (${currentLength}/${maxLength} characters)`;
                descriptionError.classList.add('visible');
                descriptionValid = false;
            } else {
                descriptionInput.classList.remove('error');
                if (descriptionInput.value.trim() !== '') {
                    descriptionInput.classList.add('valid');
                    descriptionError.textContent = `${currentLength}/${maxLength} characters`;
                    descriptionError.classList.add('visible');
                    // Change the error message color to a neutral color when it's just showing the count
                    if (currentLength <= maxLength) {
                        descriptionError.style.color = '#64748b';
                    } else {
                        descriptionError.style.color = 'var(--error)';
                    }
                } else {
                    descriptionInput.classList.remove('valid');
                    descriptionError.classList.remove('visible');
                }
                descriptionValid = true;
            }
            updateSubmitButton();
        }
        
        function validatePrice() {
            const price = parseFloat(priceInput.value);
            if (isNaN(price) || price <= 0) {
                priceInput.classList.add('error');
                priceInput.classList.remove('valid');
                priceError.classList.add('visible');
                priceValid = false;
            } else {
                priceInput.classList.remove('error');
                priceInput.classList.add('valid');
                priceError.classList.remove('visible');
                priceValid = true;
            }
            updateSubmitButton();
        }
        
        function validateCategory() {
            if (categoryInput.value === '') {
                categoryInput.classList.add('error');
                categoryInput.classList.remove('valid');
                categoryError.classList.add('visible');
                categoryValid = false;
            } else {
                categoryInput.classList.remove('error');
                categoryInput.classList.add('valid');
                categoryError.classList.remove('visible');
                categoryValid = true;
            }
            updateSubmitButton();
        }
        
        function validateStock() {
            const stock = parseInt(stockInput.value);
            if (isNaN(stock) || stock < 1) {
                stockInput.classList.add('error');
                stockInput.classList.remove('valid');
                stockError.classList.add('visible');
                stockValid = false;
            } else {
                stockInput.classList.remove('error');
                stockInput.classList.add('valid');
                stockError.classList.remove('visible');
                stockValid = true;
            }
            updateSubmitButton();
        }
        
        function validateImage() {
            if (imageInput.files.length > 0) {
                const file = imageInput.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                
                if (!validTypes.includes(file.type)) {
                    imageError.classList.add('visible');
                    imageValid = false;
                } else {
                    imageError.classList.remove('visible');
                    imageValid = true;
                }
            } else {
                imageError.classList.remove('visible');
                imageValid = true; // Image is optional
            }
            updateSubmitButton();
        }
        
        function updateSubmitButton() {
            if (nameValid && descriptionValid && priceValid && categoryValid && stockValid && imageValid) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        // Event listeners
        nameInput.addEventListener('input', validateName);
        nameInput.addEventListener('blur', validateName);
        
        // Add input event listener for real-time description validation
        descriptionInput.addEventListener('input', validateDescription);
        descriptionInput.addEventListener('blur', validateDescription);
        
        priceInput.addEventListener('input', validatePrice);
        priceInput.addEventListener('blur', validatePrice);
        
        categoryInput.addEventListener('change', validateCategory);
        categoryInput.addEventListener('blur', validateCategory);
        
        stockInput.addEventListener('input', validateStock);
        stockInput.addEventListener('blur', validateStock);
        
        imageInput.addEventListener('change', validateImage);
        
        // Form submission
        form.addEventListener('submit', function(event) {
            // Validate all fields before submission
            validateName();
            validateDescription();
            validatePrice();
            validateCategory();
            validateStock();
            validateImage();
            
            // Prevent submission if any field is invalid
            if (!(nameValid && descriptionValid && priceValid && categoryValid && stockValid && imageValid)) {
                event.preventDefault();
            }
        });
        
        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileName.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(file);
                validateImage();
            } else {
                fileName.textContent = 'No file chosen';
                preview.innerHTML = '<i class="fas fa-image" style="color: #cbd5e1; font-size: 2rem;"></i>';
            }
        }
        
        // Initialize validation on page load
        window.addEventListener('load', function() {
            validateName();
            validateDescription();
            validatePrice();
            validateCategory();
            validateStock();
            validateImage();
        });
    </script>
</body>
</html>