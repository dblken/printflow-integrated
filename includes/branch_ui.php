<?php
/**
 * branch_ui.php
 * UI helpers for branch selection and display used by the admin module.
 */

/**
 * Emit inline CSS for branch-related UI components.
 * Called in <head> via <?php render_branch_css(); ?>
 */
function render_branch_css() {
    ?>
    <style>
        /* Branch selector */
        .branch-selector-wrap { display: inline-flex; align-items: center; gap: 8px; }
        .branch-selector {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: border-color .15s;
        }
        .branch-selector:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }

        /* Branch context banner */
        .branch-banner {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            margin-bottom: 18px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            font-size: 13px;
            color: #4338ca;
            font-weight: 500;
        }
        .branch-banner svg { width: 16px; height: 16px; flex-shrink: 0; }
    </style>
    <?php
}

/**
 * Render the branch selection form / dropdown.
 *
 * Accepts either:
 *   - An associative array (the full $branchCtx from init_branch_context())
 *   - A scalar branch ID / 'all'
 *
 * @param array|string|int $ctxOrId
 * @param bool             $allowAll  Whether to show "All Branches" option
 */
function render_branch_selector($ctxOrId = 'all', $allowAll = true) {
    // Normalise argument
    if (is_array($ctxOrId)) {
        $selectedId = $ctxOrId['selected_branch_id'] ?? 'all';
    } else {
        $selectedId = $ctxOrId;
    }

    $branches = db_query("SELECT id, branch_name FROM branches WHERE status = 'Active' ORDER BY branch_name ASC") ?: [];
    ?>
    <form method="GET" class="branch-selector-wrap" style="display:inline-flex;align-items:center;gap:6px;">
        <label style="font-size:12px;font-weight:600;color:#6b7280;">Branch:</label>
        <select name="branch_id" class="branch-selector" onchange="this.form.submit()">
            <?php if ($allowAll): ?>
                <option value="all" <?php echo $selectedId === 'all' ? 'selected' : ''; ?>>All Branches</option>
            <?php endif; ?>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo (int)$b['id']; ?>" <?php echo (string)$selectedId === (string)$b['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($b['branch_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php
}

/**
 * Render a small info banner showing the currently-selected branch.
 * Hidden when "All Branches" is selected.
 *
 * @param string $branchName  The human-readable branch name (or 'All Branches')
 */
function render_branch_context_banner($branchName = '') {
    if (empty($branchName) || $branchName === 'All Branches') {
        return; // Nothing to show for "All"
    }
    ?>
    <div class="branch-banner">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
        </svg>
        Viewing: <strong><?php echo htmlspecialchars($branchName); ?></strong>
    </div>
    <?php
}

/**
 * Return an HTML badge for a branch, used inline in table rows.
 *
 * @param int    $branchId    The branch ID (0 = unassigned)
 * @param string $branchName  The branch display name
 * @return string  HTML string
 */
function get_branch_badge_html($branchId = 0, $branchName = '') {
    if (!$branchName || $branchName === 'Unknown' || !$branchId) {
        return '<span style="font-size:11px;color:#9ca3af;">—</span>';
    }

    // Cycle through a few pastel colours based on branch ID so each branch gets a consistent colour
    $palettes = [
        'background:#e0e7ff;color:#3730a3;',  // indigo
        'background:#dcfce7;color:#166534;',  // green
        'background:#fef3c7;color:#92400e;',  // amber
        'background:#fce7f3;color:#9d174d;',  // pink
        'background:#dbeafe;color:#1e40af;',  // blue
        'background:#f3e8ff;color:#6b21a8;',  // purple
    ];

    $style = $palettes[$branchId % count($palettes)];

    return sprintf(
        '<span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;%s">%s</span>',
        $style,
        htmlspecialchars($branchName)
    );
}

