<?php
/**
 * Add Product Page
 * 
 * @security Admin authentication and permissions required
 */

// Check permissions
if (!$adminAuth->hasPermission('manage_products')) {
    setAdminFlashMessage('You do not have permission to access this page.', 'danger');
    redirect('admin/');
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateAdminCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Sanitize input
        $name = Security::sanitizeInput($_POST['name'] ?? '');
        $slug = Security::sanitizeInput($_POST['slug'] ?? '');
        $sku = Security::sanitizeInput($_POST['sku'] ?? '');
        $shortDescription = Security::sanitizeInput($_POST['short_description'] ?? '');
        $description = Security::sanitizeInput($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
        $price = (float)($_POST['price'] ?? 0);
        $comparePrice = !empty($_POST['compare_price']) ? (float)$_POST['compare_price'] : null;
        $inventoryQuantity = (int)($_POST['inventory_quantity'] ?? 0);
        $lowStockThreshold = (int)($_POST['low_stock_threshold'] ?? 5);
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
        $dimensions = Security::sanitizeInput($_POST['dimensions'] ?? '');
        $metaTitle = Security::sanitizeInput($_POST['meta_title'] ?? '');
        $metaDescription = Security::sanitizeInput($_POST['meta_description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $trackInventory = isset($_POST['track_inventory']) ? 1 : 0;
        
        // Validate input
        if (empty($name)) {
            $errors[] = 'Product name is required.';
        }
        
        if (empty($sku)) {
            $errors[] = 'SKU is required.';
        } else {
            // Check if SKU already exists
            $skuQuery = "SELECT id FROM products WHERE sku = ?";
            $existingSku = $db->fetchRow($skuQuery, [$sku]);
            if ($existingSku) {
                $errors[] = 'SKU already exists. Please use a different SKU.';
            }
        }
        
        if ($price <= 0) {
            $errors[] = 'Price must be greater than 0.';
        }
        
        if ($categoryId <= 0) {
            $errors[] = 'Please select a category.';
        }
        
        if ($comparePrice && $comparePrice <= $price) {
            $errors[] = 'Compare price must be greater than regular price.';
        }
        
        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        
        // Process product creation if no validation errors
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                // Insert product
                $productQuery = "INSERT INTO products (
                    name, slug, sku, short_description, description, category_id, brand_id,
                    price, compare_price, inventory_quantity, low_stock_threshold, weight,
                    dimensions, meta_title, meta_description, is_active, is_featured,
                    track_inventory, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $productParams = [
                    $name, $slug, $sku, $shortDescription, $description, $categoryId, $brandId,
                    $price, $comparePrice, $inventoryQuantity, $lowStockThreshold, $weight,
                    $dimensions, $metaTitle, $metaDescription, $isActive, $isFeatured, $trackInventory
                ];
                
                $db->execute($productQuery, $productParams);
                $productId = $db->lastInsertId();
                
                // Handle image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $uploadedImages = [];
                    $imageCount = count($_FILES['images']['name']);
                    
                    for ($i = 0; $i < $imageCount; $i++) {
                        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                            $imageFile = [
                                'name' => $_FILES['images']['name'][$i],
                                'type' => $_FILES['images']['type'][$i],
                                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                                'error' => $_FILES['images']['error'][$i],
                                'size' => $_FILES['images']['size'][$i]
                            ];
                            
                            $uploadResult = uploadAdminFile($imageFile, 'products', ['jpg', 'jpeg', 'png', 'webp']);
                            
                            if ($uploadResult['success']) {
                                $isPrimary = $i === 0 ? 1 : 0;
                                $imageQuery = "INSERT INTO product_images (product_id, image_url, alt_text, is_primary, sort_order, created_at) 
                                               VALUES (?, ?, ?, ?, ?, NOW())";
                                $db->execute($imageQuery, [$productId, $uploadResult['filepath'], $name, $isPrimary, $i]);
                                $uploadedImages[] = $uploadResult['filepath'];
                            }
                        }
                    }
                }
                
                $db->commit();
                
                // Log activity
                logAdminActivity('product_created', [
                    'product_id' => $productId,
                    'product_name' => $name,
                    'sku' => $sku
                ]);
                
                setAdminFlashMessage('Product created successfully!', 'success');
                redirect("admin/?page=edit-product&id=$productId");
                
            } catch (Exception $e) {
                $db->rollback();
                error_log("Product creation failed: " . $e->getMessage());
                $errors[] = 'Failed to create product. Please try again.';
            }
        }
    }
}

// Get categories and brands for dropdowns
$categories = getAllCategories();
$brands = getAllBrands();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Add Product</h1>
            <p class="text-muted">Create a new product in your catalog</p>
        </div>
        <div>
            <a href="?page=products" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Products
            </a>
        </div>
    </div>
    
    <!-- Display Errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Product Form -->
    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
        
        <div class="row">
            <!-- Main Product Information -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       required>
                                <div class="invalid-feedback">Please enter a product name.</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="sku" class="form-label">SKU *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="sku" name="sku" 
                                           value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : ''; ?>" 
                                           required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateSKU()">
                                        Generate
                                    </button>
                                </div>
                                <div class="invalid-feedback">Please enter a SKU.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">URL Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" 
                                   value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>" 
                                   placeholder="Auto-generated from product name">
                            <div class="form-text">Leave empty to auto-generate from product name</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <textarea class="form-control" id="short_description" name="short_description" 
                                      rows="3" maxlength="255"><?php echo isset($_POST['short_description']) ? htmlspecialchars($_POST['short_description']) : ''; ?></textarea>
                            <div class="form-text">Brief description for product listings (max 255 characters)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Full Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="8"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="form-text">Detailed product description</div>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                <div class="invalid-feedback">Please enter a valid price.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="compare_price" class="form-label">Compare Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="compare_price" name="compare_price" 
                                           value="<?php echo isset($_POST['compare_price']) ? $_POST['compare_price'] : ''; ?>" 
                                           step="0.01" min="0">
                                </div>
                                <div class="form-text">Original price for showing discounts</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Inventory -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="inventory_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="inventory_quantity" name="inventory_quantity" 
                                       value="<?php echo isset($_POST['inventory_quantity']) ? $_POST['inventory_quantity'] : '0'; ?>" 
                                       min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" 
                                       value="<?php echo isset($_POST['low_stock_threshold']) ? $_POST['low_stock_threshold'] : '5'; ?>" 
                                       min="0">
                                <div class="form-text">Alert when stock falls below this number</div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="track_inventory" name="track_inventory" 
                                   <?php echo isset($_POST['track_inventory']) ? 'checked' : 'checked'; ?>>
                            <label class="form-check-label" for="track_inventory">
                                Track inventory for this product
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Product Images -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Product Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="file-upload-area" onclick="document.getElementById('images').click()">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5>Click to upload images</h5>
                            <p class="text-muted">Or drag and drop images here</p>
                            <p class="text-muted small">Supported formats: JPG, PNG, WebP (Max 5MB each)</p>
                        </div>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;">
                        
                        <div id="imagePreview" class="mt-3" style="display: none;">
                            <h6>Selected Images:</h6>
                            <div class="row" id="previewContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Product Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Product Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?>>
                            <label class="form-check-label" for="is_active">
                                Active (visible on website)
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                   <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">
                                Featured product
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Organization -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Organization</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="brand_id" class="form-label">Brand</label>
                            <select class="form-select" id="brand_id" name="brand_id">
                                <option value="">Select Brand</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" 
                                            <?php echo (isset($_POST['brand_id']) && $_POST['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" 
                                   value="<?php echo isset($_POST['weight']) ? $_POST['weight'] : ''; ?>" 
                                   step="0.01" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <label for="dimensions" class="form-label">Dimensions</label>
                            <input type="text" class="form-control" id="dimensions" name="dimensions" 
                                   value="<?php echo isset($_POST['dimensions']) ? htmlspecialchars($_POST['dimensions']) : ''; ?>" 
                                   placeholder="L x W x H (cm)">
                        </div>
                    </div>
                </div>
                
                <!-- SEO -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">SEO</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                   value="<?php echo isset($_POST['meta_title']) ? htmlspecialchars($_POST['meta_title']) : ''; ?>" 
                                   maxlength="60">
                            <div class="form-text">Recommended: 50-60 characters</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" 
                                      rows="3" maxlength="160"><?php echo isset($_POST['meta_description']) ? htmlspecialchars($_POST['meta_description']) : ''; ?></textarea>
                            <div class="form-text">Recommended: 150-160 characters</div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Product
                            </button>
                            <a href="?page=products" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from product name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    nameInput.addEventListener('input', function() {
        if (!slugInput.value) {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            slugInput.value = slug;
        }
    });
    
    // Image upload preview
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('imagePreview');
    const previewContainer = document.getElementById('previewContainer');
    
    imageInput.addEventListener('change', function() {
        previewContainer.innerHTML = '';
        
        if (this.files.length > 0) {
            imagePreview.style.display = 'block';
            
            Array.from(this.files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.className = 'col-md-6 mb-2';
                        col.innerHTML = `
                            <div class="position-relative">
                                <img src="${e.target.result}" class="img-fluid rounded" style="height: 100px; object-fit: cover;">
                                <span class="badge bg-primary position-absolute top-0 start-0 m-1">
                                    ${index === 0 ? 'Primary' : index + 1}
                                </span>
                            </div>
                        `;
                        previewContainer.appendChild(col);
                    };
                    reader.readAsDataURL(file);
                }
            });
        } else {
            imagePreview.style.display = 'none';
        }
    });
    
    // Drag and drop for images
    const uploadArea = document.querySelector('.file-upload-area');
    
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        imageInput.files = files;
        imageInput.dispatchEvent(new Event('change'));
    });
});

function generateSKU() {
    const name = document.getElementById('name').value;
    const categoryId = document.getElementById('category_id').value;
    
    if (!name) {
        showNotification('Please enter a product name first', 'warning');
        return;
    }
    
    makeAjaxRequest('ajax/generate-sku.php', {
        method: 'POST',
        body: JSON.stringify({
            name: name,
            category_id: categoryId
        })
    })
    .then(data => {
        if (data.success) {
            document.getElementById('sku').value = data.sku;
        } else {
            showNotification(data.message || 'Failed to generate SKU', 'danger');
        }
    });
}
</script>

<?php
// Add page-specific scripts
$pageScripts = [];
?>
