<?php
/**
 * Brands Management Page
 */

// Simple permission check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ?page=login');
    exit;
}

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add':
                $name = Security::sanitizeInput($_POST['name'] ?? '');
                $description = Security::sanitizeInput($_POST['description'] ?? '');
                $website = Security::sanitizeInput($_POST['website'] ?? '');
                $metaTitle = Security::sanitizeInput($_POST['meta_title'] ?? '');
                $metaDescription = Security::sanitizeInput($_POST['meta_description'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name)) {
                    $errors[] = 'Brand name is required.';
                } else {
                    try {
                        // Check if brand already exists
                        $existingQuery = "SELECT id FROM brands WHERE name = ?";
                        $existing = $db->fetchRow($existingQuery, [$name]);

                        if ($existing) {
                            $errors[] = 'A brand with this name already exists.';
                        } else {
                            // Generate slug
                            $slug = generateSlug($name);

                            // Insert brand
                            $insertQuery = "INSERT INTO brands (name, slug, description, website, meta_title, meta_description, is_active, sort_order, created_at, updated_at)
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                            $db->execute($insertQuery, [$name, $slug, $description, $website, $metaTitle, $metaDescription, $isActive, $sortOrder]);

                            $success = 'Brand added successfully!';
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Error adding brand: ' . $e->getMessage();
                    }
                }
                break;

            case 'edit':
                $brandId = (int)($_POST['brand_id'] ?? 0);
                $name = Security::sanitizeInput($_POST['name'] ?? '');
                $description = Security::sanitizeInput($_POST['description'] ?? '');
                $website = Security::sanitizeInput($_POST['website'] ?? '');
                $metaTitle = Security::sanitizeInput($_POST['meta_title'] ?? '');
                $metaDescription = Security::sanitizeInput($_POST['meta_description'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name)) {
                    $errors[] = 'Brand name is required.';
                } elseif ($brandId <= 0) {
                    $errors[] = 'Invalid brand ID.';
                } else {
                    try {
                        // Check if brand exists
                        $brandQuery = "SELECT * FROM brands WHERE id = ?";
                        $brand = $db->fetchRow($brandQuery, [$brandId]);

                        if (!$brand) {
                            $errors[] = 'Brand not found.';
                        } else {
                            // Check if name already exists (excluding current brand)
                            $existingQuery = "SELECT id FROM brands WHERE name = ? AND id != ?";
                            $existing = $db->fetchRow($existingQuery, [$name, $brandId]);

                            if ($existing) {
                                $errors[] = 'A brand with this name already exists.';
                            } else {
                                // Generate slug
                                $slug = generateSlug($name);

                                // Update brand
                                $updateQuery = "UPDATE brands SET name = ?, slug = ?, description = ?, website = ?, meta_title = ?, meta_description = ?, is_active = ?, sort_order = ?, updated_at = NOW()
                                               WHERE id = ?";
                                $db->execute($updateQuery, [$name, $slug, $description, $website, $metaTitle, $metaDescription, $isActive, $sortOrder, $brandId]);

                                $success = 'Brand updated successfully!';
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Error updating brand: ' . $e->getMessage();
                    }
                }
                break;

            case 'delete':
                $brandId = (int)($_POST['brand_id'] ?? 0);

                if ($brandId <= 0) {
                    $errors[] = 'Invalid brand ID.';
                } else {
                    try {
                        // Check if brand has products
                        $productCount = $db->fetchRow("SELECT COUNT(*) as count FROM products WHERE brand_id = ?", [$brandId])['count'];

                        if ($productCount > 0) {
                            $errors[] = 'Cannot delete brand. It has ' . $productCount . ' products assigned to it.';
                        } else {
                            // Delete brand
                            $db->execute("DELETE FROM brands WHERE id = ?", [$brandId]);
                            $success = 'Brand deleted successfully!';
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Error deleting brand: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get brands with product counts
try {
    $brandsQuery = "SELECT b.*,
                           (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.is_active = 1) as product_count
                    FROM brands b
                    ORDER BY b.sort_order, b.name";
    $brands = $db->fetchAll($brandsQuery);

} catch (Exception $e) {
    $brands = [];
    $errors[] = 'Error loading brands: ' . $e->getMessage();
}

// Helper function for generating slug
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Brands</h1>
            <p class="text-muted">Manage product brands</p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                <i class="fas fa-plus me-2"></i>Add Brand
            </button>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Brands Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Brands (<?php echo count($brands); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($brands)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Brand</th>
                                <th>Website</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brands as $brand): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($brand['name']); ?></h6>
                                            <?php if (!empty($brand['description'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($brand['description']); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($brand['slug'])): ?>
                                                <br><code class="small"><?php echo htmlspecialchars($brand['slug']); ?></code>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($brand['website'])): ?>
                                            <a href="<?php echo htmlspecialchars($brand['website']); ?>" target="_blank" class="text-decoration-none">
                                                <i class="fas fa-external-link-alt me-1"></i>
                                                <?php echo htmlspecialchars($brand['website']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No website</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo number_format($brand['product_count']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $brand['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $brand['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <?php if (!empty($brand['sort_order'])): ?>
                                            <br><small class="text-muted">Order: <?php echo $brand['sort_order']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if (!empty($brand['created_at'])): ?>
                                                <?php echo date('M j, Y', strtotime($brand['created_at'])); ?>
                                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($brand['created_at'])); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary"
                                                    onclick="editBrand(<?php echo $brand['id']; ?>)"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    onclick="deleteBrand(<?php echo $brand['id']; ?>, '<?php echo htmlspecialchars($brand['name']); ?>')"
                                                    title="Delete"
                                                    <?php echo ($brand['product_count'] > 0) ? 'disabled' : ''; ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-copyright fa-3x text-muted mb-3"></i>
                    <h5>No brands found</h5>
                    <p class="text-muted">Start by creating your first product brand.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                        <i class="fas fa-plus me-2"></i>Add Brand
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label">Brand Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com">
                    </div>

                    <div class="mb-3">
                        <label for="meta_title" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title">
                        <small class="form-text text-muted">SEO meta title for this brand</small>
                    </div>

                    <div class="mb-3">
                        <label for="meta_description" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea>
                        <small class="form-text text-muted">SEO meta description for this brand</small>
                    </div>

                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        <small class="form-text text-muted">Lower numbers appear first</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="brand_id" id="edit_brand_id">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Brand Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="edit_website" name="website" placeholder="https://example.com">
                    </div>

                    <div class="mb-3">
                        <label for="edit_meta_title" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="edit_meta_title" name="meta_title">
                        <small class="form-text text-muted">SEO meta title for this brand</small>
                    </div>

                    <div class="mb-3">
                        <label for="edit_meta_description" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="edit_meta_description" name="meta_description" rows="2"></textarea>
                        <small class="form-text text-muted">SEO meta description for this brand</small>
                    </div>

                    <div class="mb-3">
                        <label for="edit_sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="edit_sort_order" name="sort_order" value="0" min="0">
                        <small class="form-text text-muted">Lower numbers appear first</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBrand(brandId) {
    // Find brand data
    const brands = <?php echo json_encode($brands); ?>;
    const brand = brands.find(b => b.id == brandId);

    if (brand) {
        document.getElementById('edit_brand_id').value = brand.id;
        document.getElementById('edit_name').value = brand.name;
        document.getElementById('edit_description').value = brand.description || '';
        document.getElementById('edit_website').value = brand.website || '';
        document.getElementById('edit_meta_title').value = brand.meta_title || '';
        document.getElementById('edit_meta_description').value = brand.meta_description || '';
        document.getElementById('edit_sort_order').value = brand.sort_order || 0;
        document.getElementById('edit_is_active').checked = brand.is_active == 1;

        const modal = new bootstrap.Modal(document.getElementById('editBrandModal'));
        modal.show();
    }
}

function deleteBrand(brandId, brandName) {
    if (confirm(`Are you sure you want to delete the brand "${brandName}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="brand_id" value="${brandId}">
            <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
