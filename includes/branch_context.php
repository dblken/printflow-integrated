<?php
/**
 * branch_context.php
 * Handles branch-specific filtering and context for the admin module.
 */

/**
 * Initialize branch context from GET/SESSION.
 * Returns an array with 'selected_branch_id', 'is_all', and 'branch_name'.
 *
 * @param bool $allowAll  If false, fall back to the user's own branch_id
 */
function init_branch_context($allowAll = true) {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $selected = $_GET['branch_id'] ?? $_SESSION['selected_branch_id'] ?? 'all';

    if (!$allowAll && $selected === 'all') {
        $selected = $_SESSION['branch_id'] ?? 1;
    }

    // Coerce to int when it's numeric so comparisons work
    if ($selected !== 'all') {
        $selected = (int)$selected;
    }

    $_SESSION['selected_branch_id'] = $selected;

    // Resolve branch name for display
    $branch_name = 'All Branches';
    if ($selected !== 'all') {
        $row = db_query("SELECT branch_name FROM branches WHERE id = ?", "i", [$selected]);
        $branch_name = $row[0]['branch_name'] ?? 'Branch #' . $selected;
    }

    return [
        'selected_branch_id' => $selected,
        'is_all'             => $selected === 'all',
        'branch_name'        => $branch_name,
    ];
}

/**
 * Build SQL WHERE fragment for branch filtering.
 * Appends to $types and $params in place.
 *
 * @param string     $alias     Table alias (e.g. 'o')
 * @param int|'all'  $branchId
 * @param string     &$types    MySQLi bind-types string
 * @param array      &$params   MySQLi params array
 * @return string  SQL fragment, e.g. " AND o.branch_id = ?"
 */
function branch_where($alias, $branchId, &$types, &$params) {
    if ($branchId === 'all') return '';

    $prefix = $alias ? "$alias." : '';
    $types  .= 'i';
    $params[] = (int)$branchId;

    return " AND {$prefix}branch_id = ?";
}

/**
 * Stateless version — returns [sqlFrag, types, params].
 * Use this when you build each query independently.
 *
 * @param string     $alias
 * @param int|'all'  $branchId
 * @return array  [string $sqlFrag, string $types, array $params]
 */
function branch_where_parts($alias, $branchId) {
    if ($branchId === 'all') return ['', '', []];

    $prefix = $alias ? "$alias." : '';
    return [" AND {$prefix}branch_id = ?", 'i', [(int)$branchId]];
}
