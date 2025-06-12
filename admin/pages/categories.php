<?php
/**
 * Categories Management Page
 *
 * @security Admin authentication and permissions required
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
                $parentId = (int)($_POST['parent_id'] ?? 0);
                $status = Security::sanitizeInput($_POST['status'] ?? 'active');

                if (empty($name)) {
                    $errors[] = 'Category name is required.';
                } else {
                    try {
                        // Check if category already exists
                        $existingQuery = "SELECT id FROM categories WHERE name = ? AND parent_id = ?";
                        $existing = $db->fetchRow($existingQuery, [$name, $parentId]);

                        if ($existing) {
                            $errors[] = 'A category with this name already exists.';
                        } else {
                            // Generate slug
                            $slug = generateSlug($name);

                            // Insert category
                            $insertQuery = "INSERT INTO categories (name, slug, description, parent_id, status, created_at, updated_at)
                                           VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                            $db->execute($insertQuery, [$name, $slug, $description, $parentId ?: null, $status]);

                            $success = 'Category added successfully!';
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Error adding category: ' . $e->getMessage();
                    }
                }
                break;

            case 'edit':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $name = Security::sanitizeInput($_POST['name'] ?? '');
                $description = Security::sanitizeInput($_POST['description'] ?? '');
                $parentId = (int)($_POST['parent_id'] ?? 0);
                $status = Security::sanitizeInput($_POST['status'] ?? 'active');

                if (empty($name)) {
                    $errors[] = 'Category name is required.';
                } elseif ($categoryId <= 0) {
                    $errors[] = 'Invalid category ID.';
                } else {
                    try {
                        // Check if category exists
                        $categoryQuery = "SELECT * FROM categories WHERE id = ?";
                        $category = $db->fetchRow($categoryQuery, [$categoryId]);

                        if (!$category) {
                            $errors[] = 'Category not found.';
                        } else {
                            // Check if name already exists (excluding current category)
                            $existingQuery = "SELECT id FROM categories WHERE name = ? AND parent_id = ? AND id != ?";
                            $existing = $db->fetchRow($existingQuery, [$name, $parentId, $categoryId]);

                            if ($existing) {
                                $errors[] = 'A category with this name already exists.';
                            } else {
                                // Generate slug
                                $slug = generateSlug($name);

                                // Update category
                                $updateQuery = "UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, status = ?, updated_at = NOW()
                                               WHERE id = ?";
                                $db->execute($updateQuery, [$name, $slug, $description, $parentId ?: null, $status, $categoryId]);

                                $success = 'Category updated successfully!';
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Error updating category: ' . $e->getMessage();
                    }
                }
                break;

            case 'delete':
                $categoryId = (int)($_POST['category_id'] ?? 0);

                if ($categoryId <= 0) {
                    $errors[] = 'Invalid category ID.';
                } else {
                    try {
                        // Check if category has products
                        $productCount = $db->fetchRow("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$categoryId])['count'];

                        if ($productCount > 0) {
                            $errors[] = 'Cannot delete category. It has ' . $productCount . ' products assigned to it.';
                        } else {
                            // Check if category has subcategories
                            $subcategoryCount = $db->fetchRow("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?", [$categoryId])['count'];

                            if ($subcategoryCount > 0) {
                                $errors[] = 'Cannot delete category. It has ' . $subcategoryCount . ' subcategories.';
                            } else {
                                // Delete category
                                $db->execute("DELETE FROM categories WHERE id = ?", [$categoryId]);
                                $success = 'Category deleted successfully!';
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Error deleting category: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get categories with hierarchy
try {
    // Simple categories query
    $categoriesQuery = "SELECT * FROM categories ORDER BY name";
    $categories = $db->fetchAll($categoriesQuery);

    // Get parent categories for dropdown
    $parentCategories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

} catch (Exception $e) {
    $categories = [];
    $parentCategories = [];
    $errors[] = 'Error loading categories: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Categories</h1>
            <p class="text-muted">Manage product categories and hierarchies</p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus me-2"></i>Add Category
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

    <!-- Categories Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Categories (<?php echo count($categories); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($categories)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Parent</th>
                                <th>Products</th>
                                <th>Subcategories</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($category['parent_id'])): ?>
                                                <span class="text-muted me-2">└─</span>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h6>
                                                <?php if (!empty($category['description'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($category['parent_id'])): ?>
                                            <span class="badge bg-light text-dark">Parent: <?php echo $category['parent_id']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Root Category</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">0</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">0</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($category['status'] ?? 'active') === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($category['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if (!empty($category['created_at'])): ?>
                                                <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($category['created_at'])); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary"
                                                    onclick="editCategory(<?php echo $category['id']; ?>)"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')"
                                                    title="Delete">
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
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h5>No categories found</h5>
                    <p class="text-muted">Start by creating your first product category.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-2"></i>Add Category
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">Root Category</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_parent_id" class="form-label">Parent Category</label>
                        <select class="form-select" id="edit_parent_id" name="parent_id">
                            <option value="">Root Category</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(categoryId) {
    // Find category data
    const categories = <?php echo json_encode($categories); ?>;
    const category = categories.find(c => c.id == categoryId);

    if (category) {
        document.getElementById('edit_category_id').value = category.id;
        document.getElementById('edit_name').value = category.name;
        document.getElementById('edit_description').value = category.description || '';
        document.getElementById('edit_parent_id').value = category.parent_id || '';
        document.getElementById('edit_status').value = category.status;

        const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        modal.show();
    }
}

function deleteCategory(categoryId, categoryName) {
    if (confirm(`Are you sure you want to delete the category "${categoryName}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="category_id" value="${categoryId}">
            <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Helper function to generate slug
function generateSlug(text) {
    return text.toLowerCase()
               .replace(/[^\w\s-]/g, '')
               .replace(/[\s_-]+/g, '-')
               .replace(/^-+|-+$/g, '');
}
</script>

<?php
// Helper function for generating slug
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}
?>
