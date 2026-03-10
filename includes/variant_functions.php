<?php
/**
 * variant_functions.php
 * Helper functions for product variants and material deductions.
 */

/**
 * Get all variants for a product, with their material count.
 */
function get_variants_by_product($productId) {
    return db_query(
        "SELECT pv.*, 
                (SELECT COUNT(*) FROM product_variant_materials pvm WHERE pvm.variant_id = pv.variant_id) as material_count
         FROM product_variants pv
         WHERE pv.product_id = ?
         ORDER BY pv.variant_id ASC",
        "i", [(int)$productId]
    ) ?: [];
}

/**
 * Get the Bill of Materials (BOM) for a specific variant.
 */
function get_variant_materials($variantId) {
    return db_query(
        "SELECT pvm.*, m.material_name, m.unit, mc.category_name
         FROM product_variant_materials pvm
         JOIN materials m ON pvm.material_id = m.material_id
         JOIN material_categories mc ON m.category_id = mc.category_id
         WHERE pvm.variant_id = ?
         ORDER BY mc.category_name, m.material_name",
        "i", [(int)$variantId]
    ) ?: [];
}

/**
 * Save (replace) the Bill of Materials for a variant.
 *
 * @param int   $variantId
 * @param array $bom  [['material_id' => int, 'quantity_required' => float], ...]
 */
function save_variant_materials($variantId, array $bom) {
    // Delete existing BOM rows for this variant
    db_execute("DELETE FROM product_variant_materials WHERE variant_id = ?", "i", [$variantId]);

    foreach ($bom as $row) {
        $mid = (int)($row['material_id'] ?? 0);
        $qty = (float)($row['quantity_required'] ?? 0);
        if ($mid > 0 && $qty > 0) {
            db_execute(
                "INSERT INTO product_variant_materials (variant_id, material_id, quantity_required) VALUES (?, ?, ?)",
                "iid", [$variantId, $mid, $qty]
            );
        }
    }
}

/**
 * Deduct materials based on product variants when an order is marked Completed.
 *
 * @param int $orderId
 * @return array ['success' => bool, 'errors' => string[]]
 */
function deduct_materials_by_variant($orderId) {
    $errors = [];

    try {
        // Get order items with variant info
        $items = db_query(
            "SELECT oi.order_item_id, oi.variant_id, oi.quantity
             FROM order_items oi
             WHERE oi.order_id = ? AND oi.variant_id IS NOT NULL",
            "i", [$orderId]
        ) ?: [];

        foreach ($items as $item) {
            $bom = get_variant_materials((int)$item['variant_id']);
            foreach ($bom as $row) {
                $needed = (float)$row['quantity_required'] * (int)$item['quantity'];
                db_execute(
                    "UPDATE materials SET current_stock = GREATEST(0, current_stock - ?) WHERE material_id = ?",
                    "di", [$needed, $row['material_id']]
                );
            }
        }
    } catch (Exception $e) {
        $errors[] = "Material deduction error: " . $e->getMessage();
    }

    return ['success' => empty($errors), 'errors' => $errors];
}
