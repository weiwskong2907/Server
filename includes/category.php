<?php
/**
 * Category helper functions
 */

/**
 * Create category
 */
function create_category($name, $description = null) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Validate input
    if (empty($name)) {
        return ['success' => false, 'message' => 'Category name is required'];
    }
    
    // Check if category exists
    $existing = get_row(
        "SELECT id FROM categories WHERE name = ?",
        "s",
        [$name]
    );
    
    if ($existing) {
        return ['success' => false, 'message' => 'Category already exists'];
    }
    
    // Insert category
    $category_data = [
        'name' => $name,
        'description' => $description
    ];
    
    $category_id = insert_data('categories', $category_data);
    
    if (!$category_id) {
        return ['success' => false, 'message' => 'Failed to create category'];
    }
    
    // Log activity
    log_activity($_SESSION['user_id'], 'category_create', 'category', $category_id, 'Created new category');
    
    return [
        'success' => true,
        'message' => 'Category created successfully',
        'category_id' => $category_id
    ];
}

/**
 * Update category
 */
function update_category($category_id, $name, $description = null) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Validate input
    if (empty($name)) {
        return ['success' => false, 'message' => 'Category name is required'];
    }
    
    // Check if category exists
    $category = get_row(
        "SELECT id FROM categories WHERE id = ?",
        "i",
        [$category_id]
    );
    
    if (!$category) {
        return ['success' => false, 'message' => 'Category not found'];
    }
    
    // Check if new name conflicts with existing category
    $existing = get_row(
        "SELECT id FROM categories WHERE name = ? AND id != ?",
        "si",
        [$name, $category_id]
    );
    
    if ($existing) {
        return ['success' => false, 'message' => 'Category name already exists'];
    }
    
    // Update category
    $updated = update_data(
        'categories',
        [
            'name' => $name,
            'description' => $description
        ],
        'id = ?',
        'i',
        [$category_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to update category'];
    }
    
    // Log activity
    log_activity($_SESSION['user_id'], 'category_update', 'category', $category_id, 'Updated category');
    
    return ['success' => true, 'message' => 'Category updated successfully'];
}

/**
 * Delete category
 */
function delete_category($category_id) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Check if category exists
    $category = get_row(
        "SELECT id FROM categories WHERE id = ?",
        "i",
        [$category_id]
    );
    
    if (!$category) {
        return ['success' => false, 'message' => 'Category not found'];
    }
    
    // Check if category has posts
    $has_posts = get_row(
        "SELECT COUNT(*) as count FROM posts WHERE category_id = ?",
        "i",
        [$category_id]
    )['count'] > 0;
    
    if ($has_posts) {
        return ['success' => false, 'message' => 'Cannot delete category with posts'];
    }
    
    // Delete category
    $deleted = delete_data('categories', 'id = ?', 'i', [$category_id]);
    
    if (!$deleted) {
        return ['success' => false, 'message' => 'Failed to delete category'];
    }
    
    // Log activity
    log_activity($_SESSION['user_id'], 'category_delete', 'category', $category_id, 'Deleted category');
    
    return ['success' => true, 'message' => 'Category deleted successfully'];
}

/**
 * Get category with details
 */
function get_category($category_id) {
    $category = get_row(
        "SELECT * FROM categories WHERE id = ?",
        "i",
        [$category_id]
    );
    
    if (!$category) {
        return null;
    }
    
    // Get post count
    $category['post_count'] = get_row(
        "SELECT COUNT(*) as count FROM posts WHERE category_id = ?",
        "i",
        [$category_id]
    )['count'];
    
    return $category;
}

/**
 * Get all categories
 */
function get_categories() {
    $categories = get_rows(
        "SELECT c.*, COUNT(p.id) as post_count 
         FROM categories c 
         LEFT JOIN posts p ON c.id = p.category_id 
         GROUP BY c.id 
         ORDER BY c.name ASC"
    );
    
    return $categories;
}

/**
 * Get categories with pagination
 */
function get_categories_paginated($page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM categories",
        "i",
        []
    )['total'];
    
    // Get categories
    $categories = get_rows(
        "SELECT c.*, COUNT(p.id) as post_count 
         FROM categories c 
         LEFT JOIN posts p ON c.id = p.category_id 
         GROUP BY c.id 
         ORDER BY c.name ASC 
         LIMIT ? OFFSET ?",
        "ii",
        [$per_page, $offset]
    );
    
    return [
        'categories' => $categories,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
} 