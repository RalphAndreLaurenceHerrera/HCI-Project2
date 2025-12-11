<?php
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
// ---------- Helpers ----------
function build_qs($overrides = []) {
    $params = [
        'q' => isset($_GET['q']) ? $_GET['q'] : '',
        'sort_by' => isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created',
        'order' => isset($_GET['order']) ? $_GET['order'] : 'DESC',
        'per_page' => isset($_GET['per_page']) ? $_GET['per_page'] : 6,
        'page' => isset($_GET['page']) ? $_GET['page'] : 1
    ];
    foreach ($overrides as $k => $v) $params[$k] = $v;
    return http_build_query($params);
}

/**
 * Renders a single notice card (returns HTML string).
 * Automatically shows the Important badge if the row includes noticeImportant = 1.
 */
function render_notice_card($n) {
    $link = $n['noticeLinkRelated'] ? $n['noticeLinkRelated'] : "notice-detail.php?id=" . urlencode($n['noticeID']);
    $title = htmlspecialchars($n['noticeTitle']);
    $summary = htmlspecialchars(mb_strimwidth($n['noticeSummary'] ?: $n['noticeBody'], 0, 220, '...'));
    $created = htmlspecialchars($n['noticeCreated']);
    $updated = !empty($n['noticeUpdated']) ? htmlspecialchars($n['noticeUpdated']) : '';
    $img = !empty($n['noticeImageLocation']) ? '<img src="'.htmlspecialchars($n['noticeImageLocation']).'" alt="">' : '';
    // Show badge if either noticeImportant column exists and is truthy OR if an external flag was set
    $isImportant = (isset($n['noticeImportant']) && intval($n['noticeImportant']) === 1)
                   || (isset($n['important']) && $n['important']); // preserves backward compatibility
    $impBadge = $isImportant ? '<span class="badge-important">Important</span>' : '';

    return "
    <div class=\"notice\">
        $img
        <div style=\"flex:1;\">
            <div>
                <span class=\"notice-title\">$title</span> $impBadge
            </div>
            <div class=\"meta small\">
                $created" . ($updated ? " • updated $updated" : "") . "
            </div>
            <p>$summary</p>
            <a href=\"" . htmlspecialchars($link) . "\">Read more</a>
        </div>
    </div>";
}


// ---------- Request params (both GET and AJAX) ----------
$q = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
$sort_by = (isset($_REQUEST['sort_by']) && in_array($_REQUEST['sort_by'], ['created','updated'])) ? $_REQUEST['sort_by'] : 'created';
$order = (isset($_REQUEST['order']) && in_array(strtoupper($_REQUEST['order']), ['ASC','DESC'])) ? strtoupper($_REQUEST['order']) : 'DESC';
$page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
$per_page = isset($_REQUEST['per_page']) ? max(1, intval($_REQUEST['per_page'])) : 6;

// tokenized search building (title, summary, body)
$where_clauses = ["noticeActive = 1"];
$params = [];
$types = "";

if ($q !== "") {
    $tokens = preg_split('/\s+/', $q);
    $tokenClauses = [];
    foreach ($tokens as $t) {
        $tokenClauses[] = "(noticeTitle LIKE ? OR noticeSummary LIKE ? OR noticeBody LIKE ?)";
        $like = '%' . $t . '%';
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types .= "sss";
    }
    if (count($tokenClauses) > 0) {
        $where_clauses[] = '(' . implode(' AND ', $tokenClauses) . ')';
    }
}
$where_sql = count($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// sorting clause
if ($sort_by === 'created') {
    $order_by_sql = "ORDER BY noticeCreated $order";
} else {
    $order_by_sql = "ORDER BY COALESCE(noticeUpdated, noticeCreated) $order";
}

// ------------------ Important notices (always filtered by active + search) ------------------
$important_sql = "SELECT noticeID, noticeTitle, noticeSummary, noticeImageLocation, noticeCreated, noticeUpdated, noticeLinkRelated, noticeImportant
                  FROM Notices
                  WHERE noticeActive = 1 AND noticeImportant = 1";

$imp_params = [];
$imp_types = "";
if ($q !== "") {
    $tokens = preg_split('/\s+/', $q);
    $imp_tokenClauses = [];
    foreach ($tokens as $t) {
        $imp_tokenClauses[] = "(noticeTitle LIKE ? OR noticeSummary LIKE ? OR noticeBody LIKE ?)";
        $like = '%' . $t . '%';
        $imp_params[] = $like; $imp_params[] = $like; $imp_params[] = $like;
        $imp_types .= "sss";
    }
    if (count($imp_tokenClauses) > 0) {
        $important_sql .= " AND (" . implode(' AND ', $imp_tokenClauses) . ")";
    }
}
$important_sql .= " ORDER BY noticeCreated DESC";

$imp_stmt = $conn->prepare($important_sql);
if ($imp_stmt === false) { die("Prepare failed (important): " . $conn->error); }
if ($imp_types !== "") $imp_stmt->bind_param($imp_types, ...$imp_params);
$imp_stmt->execute();
$imp_res = $imp_stmt->get_result();
$important_notices = $imp_res->fetch_all(MYSQLI_ASSOC);

// ------------------ Pagination & list ------------------
// count total
$count_sql = "SELECT COUNT(*) AS total FROM Notices $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt === false) { die("Prepare failed (count): " . $conn->error); }
if ($types !== "") $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = (int)$count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, ceil($total / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// fetch list
$list_sql = "SELECT noticeID, noticeTitle, noticeSummary, noticeBody, noticeImageLocation, noticeCreated, noticeUpdated, noticeLinkRelated, noticeImportant
             FROM Notices
             $where_sql
             $order_by_sql
             LIMIT ? OFFSET ?";

$list_stmt = $conn->prepare($list_sql);
if ($list_stmt === false) { die("Prepare failed (list): " . $conn->error); }

// bind params: search params (if any) then per_page (i) and offset (i)
if ($types === "") {
    $list_stmt->bind_param("ii", $per_page, $offset);
} else {
    $bind_types = $types . "ii";
    $bind_vals = array_merge($params, [$per_page, $offset]);
    // mysqli bind_param requires references
    $tmp = [];
    $tmp[] = $bind_types;
    foreach ($bind_vals as $k => $v) $tmp[] = &$bind_vals[$k];
    call_user_func_array([$list_stmt, 'bind_param'], $tmp);
}
$list_stmt->execute();
$list_res = $list_stmt->get_result();
$notices = $list_res->fetch_all(MYSQLI_ASSOC);

// If AJAX request: return JSON containing both important_html and list_html and pagination info
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // build important HTML
    ob_start();
    if (count($important_notices) > 0) {
        echo '<div class="important-wrapper"><h2>Important Notices</h2>';
        foreach ($important_notices as $imp) {
            echo '<div class="important">';
            if ($imp['noticeImageLocation']) {
                echo '<img src="'.htmlspecialchars($imp['noticeImageLocation']).'" alt="">';
            }
            echo '<div>';
            echo '<h3>'.htmlspecialchars($imp['noticeTitle']).' <span class="badge-important">Important</span></h3>';
            echo '<div class="meta small">'.htmlspecialchars($imp['noticeCreated']);
            if ($imp['noticeUpdated']) echo ' • updated '.htmlspecialchars($imp['noticeUpdated']);
            echo '</div>';
            echo '<p>'.htmlspecialchars(mb_strimwidth($imp['noticeSummary'] ?: $imp['noticeBody'], 0, 220, '...')).'</p>';
            $link = $imp['noticeLinkRelated'] ? $imp['noticeLinkRelated'] : "/jboymakiandbento/notice-detail.php?id=" . urlencode($imp['noticeID']);
            echo '<div><a href="'.htmlspecialchars($link).'">Read more</a></div>';
            echo '</div></div>';
        }
        echo '</div>';
    } else {
        echo ''; // empty important section
    }
    $important_html = ob_get_clean();

    // build list HTML
    ob_start();
    if (count($notices) === 0) {
        echo '<p class="small">No notices found.</p>';
    } else {
        foreach ($notices as $n) {
            echo render_notice_card($n);
        }
    }
    $list_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'important_html' => $important_html,
        'list_html' => $list_html,
        'total' => $total,
        'page' => $page,
        'total_pages' => $total_pages
    ]);
    exit;
}

// ---------- Non-AJAX: render full page ----------

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>Notices | JBOY MAKI and BENTI Food House</title>
        <!-- Stylesheet -->
        <link rel="stylesheet" href="/jboymakiandbento/assets/css/notice.css">
    </head>
    <body>
        <main>
            <h1>Notices</h1>

            <div class="controls">
                <input id="searchInput" type="text" placeholder="Search (title, summary, body...)" value="<?= htmlspecialchars($q) ?>" style="min-width:300px;">
                <button id="toggleFiltersBtn">Filters ▾</button>

                <div id="filters" class="filters">
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <div class="sort-buttons" role="tablist" aria-label="Sort">
                            <button id="sort-updated" class="sort-button" data-sort="updated" aria-pressed="false" type="button">
                                Updated <span class="arrow" aria-hidden="true"></span>
                            </button>
                            <button id="sort-created" class="sort-button" data-sort="created" aria-pressed="false" type="button">
                                Created <span class="arrow" aria-hidden="true"></span>
                            </button>
                        </div>

                        <label>
                            Per page:
                            <select id="perPageSelect">
                                <option value="4" <?= $per_page==4 ? 'selected' : '' ?>>4</option>
                                <option value="6" <?= $per_page==6 ? 'selected' : '' ?>>6</option>
                                <option value="10" <?= $per_page==10 ? 'selected' : '' ?>>10</option>
                            </select>
                        </label>

                        <button id="applyFiltersBtn" type="button">Apply</button>
                        <button id="resetBtn" type="button">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Important (initial server render) -->
            <div id="importantContainer">
            <?php
            if (count($important_notices) > 0) {
                echo '<div class="important-wrapper"><h2>Important Notices</h2>';
                foreach ($important_notices as $imp) {
                    echo '<div class="important">';
                    if ($imp['noticeImageLocation']) echo '<img src="'.htmlspecialchars($imp['noticeImageLocation']).'" alt="">';
                    echo '<div>';
                    echo '<h3>'.htmlspecialchars($imp['noticeTitle']).' <span class="badge-important">Important</span></h3>';
                    echo '<div class="meta small">'.htmlspecialchars($imp['noticeCreated']);
                    if ($imp['noticeUpdated']) echo ' • updated '.htmlspecialchars($imp['noticeUpdated']);
                    echo '</div>';
                    echo '<p>'.htmlspecialchars(mb_strimwidth($imp['noticeSummary'] ?: $imp['noticeBody'], 0, 220, '...')).'</p>';
                    $link = $imp['noticeLinkRelated'] ? $imp['noticeLinkRelated'] : "/jboymakiandbento/notice-detail.php?id=" . urlencode($imp['noticeID']);
                    echo '<div><a href="'.htmlspecialchars($link).'">Read more</a></div>';
                    echo '</div></div>';
                }
                echo '</div>';
            }
            ?>
            </div>

            <!-- Notices list -->
            <div id="listContainer">
            <?php
            if (count($notices) === 0) {
                echo '<p class="small">No notices found.</p>';
            } else {
                foreach ($notices as $n) {
                    echo render_notice_card($n);
                }
            }
            ?>
            </div>

            <!-- Pagination (initial server render) -->
            <div id="paginationContainer">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a class="page-link" data-page="<?= $page - 1 ?>" href="?<?= build_qs(['page'=> $page - 1]) ?>">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php
                    $window = 5;
                    $start = max(1, $page - floor($window/2));
                    $end = min($total_pages, $start + $window - 1);
                    if ($end - $start + 1 < $window) $start = max(1, $end - $window + 1);
                    for ($p = $start; $p <= $end; $p++): ?>
                        <a class="page-link <?= $p === $page ? 'page-current' : '' ?>" data-page="<?= $p ?>" href="?<?= build_qs(['page'=>$p]) ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a class="page-link" data-page="<?= $page + 1 ?>" href="?<?= build_qs(['page'=> $page + 1]) ?>">Next &raquo;</a>
                    <?php endif; ?>

                    <div style="margin-left:12px;" class="small">Page <?= $page ?> of <?= $total_pages ?> • <?= $total ?> results</div>
                </div>
            </div>
        </main>
        <script>
            (function(){
                // UI references
                const searchInput = document.getElementById('searchInput');
                const toggleFiltersBtn = document.getElementById('toggleFiltersBtn');
                const filtersDiv = document.getElementById('filters');
                const applyFiltersBtn = document.getElementById('applyFiltersBtn');
                const resetBtn = document.getElementById('resetBtn');
                const perPageSelect = document.getElementById('perPageSelect');
                const sortButtons = document.querySelectorAll('.sort-button');

                // state
                let state = {
                    q: <?= json_encode($q) ?>,
                    sort_by: <?= json_encode($sort_by) ?>,
                    order: <?= json_encode($order) ?>,
                    page: <?= json_encode($page) ?>,
                    per_page: <?= json_encode($per_page) ?>
                };

                // Debounce helper
                function debounce(fn, delay) {
                    let t;
                    return function(...args) {
                        clearTimeout(t);
                        t = setTimeout(() => fn.apply(this, args), delay);
                    };
                }

                // Toggle filters
                toggleFiltersBtn.addEventListener('click', () => {
                    filtersDiv.style.display = filtersDiv.style.display === 'none' || filtersDiv.style.display === '' ? 'block' : 'none';
                });
                
                // SVG icons (small, crisp chevrons)
                const upIcon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 15l6-6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                const downIcon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

                // Elements
                const sortUpdatedBtn = document.getElementById('sort-updated');
                const sortCreatedBtn = document.getElementById('sort-created');

                // helper to set arrow SVG and pressed state
                function setSortUI(sort_by, order) {
                    // reset
                    [sortUpdatedBtn, sortCreatedBtn].forEach(b => {
                        b.setAttribute('aria-pressed', 'false');
                        b.classList.remove('sort-active');
                        const arrow = b.querySelector('.arrow');
                        if (arrow) arrow.innerHTML = downIcon; // default show down
                    });

                    // choose active button and arrow direction
                    const activeBtn = sort_by === 'updated' ? sortUpdatedBtn : sortCreatedBtn;
                    activeBtn.setAttribute('aria-pressed', 'true');
                    activeBtn.classList.add('sort-active');
                    const activeArrow = activeBtn.querySelector('.arrow');
                    if (activeArrow) activeArrow.innerHTML = (order === 'ASC' ? upIcon : downIcon);
                }

                // click handler: if same key clicked -> toggle order, otherwise set order = DESC by default
                function sortBtnHandler(evt) {
                    const btn = evt.currentTarget;
                    const thisSort = btn.dataset.sort;
                    if (state.sort_by === thisSort) {
                        // toggle order
                        state.order = (state.order === 'DESC') ? 'ASC' : 'DESC';
                    } else {
                        state.sort_by = thisSort;
                        state.order = 'DESC'; // default when switching
                    }
                    state.page = 1;
                    setSortUI(state.sort_by, state.order);
                    fetchNotices();
                }

                // attach handlers
                sortUpdatedBtn.addEventListener('click', sortBtnHandler);
                sortCreatedBtn.addEventListener('click', sortBtnHandler);

                // initialize UI to current server state
                setSortUI(state.sort_by, state.order);

                // Apply filters
                applyFiltersBtn.addEventListener('click', () => {
                    state.per_page = parseInt(perPageSelect.value, 10);
                    state.page = 1;
                    fetchNotices();
                });

                // Reset
                resetBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    perPageSelect.value = '6';
                    state = { q:'', sort_by:'created', order:'DESC', page:1, per_page:6 };
                    sortButtons.forEach(b => b.classList.remove('sort-active'));
                    // set defaults for sort buttons UI
                    document.querySelector('.sort-button[data-sort="created"][data-order="DESC"]').classList.add('sort-active');
                    fetchNotices();
                });

                // Pagination click (delegation)
                document.getElementById('paginationContainer').addEventListener('click', function(e){
                    const a = e.target.closest('a.page-link');
                    if (!a) return;
                    e.preventDefault();
                    const p = parseInt(a.dataset.page, 10);
                    if (!isNaN(p)) {
                        state.page = p;
                        fetchNotices();
                    }
                });

                // Live search (debounced)
                const onSearch = debounce(() => {
                    state.q = searchInput.value.trim();
                    state.page = 1;
                    fetchNotices();
                }, 400);
                searchInput.addEventListener('input', onSearch);

                // fetchNotices via AJAX
                function fetchNotices() {
                    const params = new URLSearchParams();
                    params.set('ajax', '1');
                    params.set('q', state.q);
                    params.set('sort_by', state.sort_by);
                    params.set('order', state.order);
                    params.set('page', state.page);
                    params.set('per_page', state.per_page);

                    fetch('/jboymakiandbento/notices-list.php?' + params.toString())
                        .then(r => r.json())
                        .then(data => {
                            document.getElementById('importantContainer').innerHTML = data.important_html || '';
                            document.getElementById('listContainer').innerHTML = data.list_html || '<p class="small">No notices found.</p>';

                            // rebuild pagination
                            rebuildPagination(data.page, data.total_pages, data.total);
                        })
                        .catch(err => console.error('Fetch error', err));
                }

                function rebuildPagination(page, total_pages, total) {
                    const pc = document.getElementById('paginationContainer');
                    let html = '<div class="pagination">';
                    if (page > 1) {
                        html += `<a class="page-link" data-page="${page-1}" href="#">« Prev</a>`;
                    }
                    let window = 5;
                    let start = Math.max(1, page - Math.floor(window/2));
                    let end = Math.min(total_pages, start + window - 1);
                    if (end - start + 1 < window) start = Math.max(1, end - window + 1);
                    for (let p = start; p <= end; p++) {
                        html += `<a class="page-link ${p===page ? 'page-current' : ''}" data-page="${p}" href="#">${p}</a>`;
                    }
                    if (page < total_pages) {
                        html += `<a class="page-link" data-page="${page+1}" href="#">Next »</a>`;
                    }
                    html += `<div style="margin-left:12px;" class="small">Page ${page} of ${total_pages} • ${total} results</div>`;
                    html += '</div>';
                    pc.innerHTML = html;
                }

                // init: wire default sort active button if hidden state
                // make the appropriate sort button active on load
                (function initSortUI(){
                    sortButtons.forEach(b=>b.classList.remove('sort-active'));
                    const selector = `.sort-button[data-sort="${state.sort_by}"][data-order="${state.order}"]`;
                    const activeBtn = document.querySelector(selector);
                    if (activeBtn) activeBtn.classList.add('sort-active');
                })();

            })();
        </script>
    </body>
</html>
<?php 
$conn->close();
?>