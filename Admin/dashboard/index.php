<?php
$PAGE_TITLE = "Admin-Panel";
require_once __DIR__ . "/header.php";
// CREATE A CSRF TOKEN 
if (empty($_SESSION['csrfToken'])) {
  $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}



// FETCH SKILLS
$skillsQuery = $connection->prepare("SELECT id, name FROM skills");
// Execute
$skillsQuery->execute();
// Bind results
$skillsQuery->bind_result($id, $name);
// Create map
$skillsMap = [];
while ($skillsQuery->fetch()) {
  $skillsMap[$id] = $name;
}
// Close statement and connection
$skillsQuery->close();



// Get the search term from the URL and sanitize it
$searchTerm = $_GET['search'] ?? '';
$searchFilter = '';
$searchParam = '';

if (!empty($searchTerm)) {
  // We'll use this in a LIKE query, so we add wildcards
  $searchParam = '%' . strtolower($searchTerm) . '%';
  // This SQL fragment will be added to the WHERE clause
  $searchFilter = "AND (LOWER(u.first_name) LIKE ? OR LOWER(u.last_name) LIKE ? OR LOWER(u.email) LIKE ?)";
}



// Get the selected skill name from the dropdown
$dropdownSort = strtolower($_GET['dropdown_sort'] ?? ''); // Convert to lowercase immediately
// Check if a skill filter is active
$skillFilter = '';
$skillId = null; // Initialize skillId to null for safe binding
// --- START OF MODIFIED SECTION ---
if (!empty($dropdownSort)) {
    // Find the skill ID from the skill name using the skillsMap
    $foundSkillId = array_search($dropdownSort, array_map('strtolower', $skillsMap));
    // If a valid skill ID is found, set up the skill filter using a subquery
    if ($foundSkillId !== false) {
        $skillId = $foundSkillId; // Store the found skill ID for binding
        // This filter checks if the user's ID exists IN the set of user_ids
        // who possess the selected skill. This filters the *users* first.
        $skillFilter = "AND u.id IN (SELECT user_id FROM user_skills WHERE skill_id = ?)";
    }
}




// Initialize data array
$data = [];
// Pagination setup
$entriesPerPage = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $entriesPerPage;
// Sort option
$sortOption = $_GET['sort'] ?? '';
$orderBy = "first_name ASC"; // Default order as per docs

switch ($sortOption) {
  case 'first_name_asc':
    $orderBy = "first_name ASC";
    break;
  case 'last_name_asc':
    $orderBy = 'last_name ASC';
    break;
  case 'first_name_desc':
    $orderBy = "first_name DESC";
    break;
  case 'last_name_desc':
    $orderBy = "last_name DESC";
    break;
  case 'email_asc':
    $orderBy = 'email ASC';
    break;
  case 'email_desc':
    $orderBy = 'email DESC';
    break;
}


// Count total rows for pagination (this also needs to be updated)
// Count total rows for pagination
$totalQuery = "SELECT COUNT(DISTINCT u.id) FROM users u LEFT JOIN user_skills us ON u.id = us.user_id WHERE 1=1 $skillFilter $searchFilter";
$totalStmt = mysqli_prepare($connection, $totalQuery);
if ($totalStmt) {
  if (!empty($skillFilter) && !empty($searchFilter)) {
    mysqli_stmt_bind_param($totalStmt, "isss", $skillId, $searchParam, $searchParam, $searchParam);
  } elseif (!empty($skillFilter)) {
    mysqli_stmt_bind_param($totalStmt, "i", $skillId);
  } elseif (!empty($searchFilter)) {
    mysqli_stmt_bind_param($totalStmt, "sss", $searchParam, $searchParam, $searchParam);
  }

  mysqli_stmt_execute($totalStmt);
  mysqli_stmt_bind_result($totalStmt, $totalRows);
  mysqli_stmt_fetch($totalStmt);
  mysqli_stmt_close($totalStmt);
}
$totalPages = ceil($totalRows / $entriesPerPage);


// Fetch paginated and sorted data
$sql = "SELECT 
    u.id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    u.is_email_verified, 
    u.created_at,
    s.name AS skill_name
FROM users u
LEFT JOIN user_skills us ON u.id = us.user_id
LEFT JOIN skills s ON us.skill_id = s.id
WHERE 1=1 $skillFilter $searchFilter
ORDER BY $orderBy
LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($connection, $sql);


if ($stmt) {
  if (!empty($skillFilter) && !empty($searchFilter)) {
    mysqli_stmt_bind_param($stmt, "isssii", $skillId, $searchParam, $searchParam, $searchParam, $entriesPerPage, $offset);
  } elseif (!empty($skillFilter)) {
    mysqli_stmt_bind_param($stmt, "iii", $skillId, $entriesPerPage, $offset);
  } elseif (!empty($searchFilter)) {
    mysqli_stmt_bind_param($stmt, "sssii", $searchParam, $searchParam, $searchParam, $entriesPerPage, $offset);
  } else {
    mysqli_stmt_bind_param($stmt, "ii", $entriesPerPage, $offset);
  }

  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name, $email, $is_email_verified, $created_at, $skill_name);
  $data = [];
  $currentUserId = null;
  while (mysqli_stmt_fetch($stmt)) {
    if ($id !== $currentUserId) {
      // New user, add to data array
      $data[$id] = [
        'id' => $id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'is_email_verified' => $is_email_verified,
        'created_at' => $created_at,
        'skills' => []
      ];
      $currentUserId = $id;
    }

    // Add the skill to the current user's skills array
    if ($skill_name) {
      $data[$id]['skills'][] = $skill_name;
    }
  }
  // Convert the associative array to a simple array if needed
  $data = array_values($data);

  mysqli_stmt_close($stmt);
} else {
  echo "Query preparation failed: " . mysqli_error($connection);
}

?>



<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2">
    <h1>Customers</h1>
  </div>

  <div class="table-responsive small">
    <div class="mb-3 p-2 d-flex flex-column flex-md-row justify-content-md-end align-items-stretch align-items-md-center gap-2 gap-md-3 rounded-md">
      <!-- SEARCH BAR -->
      <form method="get" class="d-flex w-90 flex-grow-1">
        <input type="text" name="search" class="form-control form-control-sm flex-grow-1" placeholder="Search by name or email..."
          value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit" class="btn btn-sm btn-primary ms-2 w-auto d-flex align-items-centter" style="'width:150px;">
          <i class="bi bi-search me-1"></i>Search
        </button>
        <?php
        // This preserves existing filters like the skill dropdown
        foreach ($_GET as $key => $value) {
          if ($key !== 'search' && $key !== 'page') {
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
          }
        }
        ?>
      </form>
      <!-- DROP DOWN -->
      <form method="get" class="w-100 w-md-auto" style="max-width: 200px;">
        <div class="input-group input-group-sm">
          <select name="dropdown_sort" class="form-select" onchange="this.form.submit()">
            <option value="">Filter by Skill</option>

            <?php foreach ($skillsMap as $id => $name): ?>
              <?php
              $optionValue = strtolower($name);
              ?>
              <option value="<?= htmlspecialchars($optionValue) ?>" <?= $dropdownSort === $optionValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($name) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php
        // Loop through all existing GET parameters and create hidden inputs
        foreach ($_GET as $key => $value) {
          // Exclude the dropdown_sort parameter to avoid duplication
          if ($key !== 'dropdown_sort') {
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
          }
        }
        ?>
      </form>
      <!-- BULK DELETE -->
      <button id="deleteSelected" class="btn btn-danger btn-sm d-flex align-items-centter" style="width:100px;" disabled>
        <i class="bi bi-trash3"></i>&nbsp; Delete
      </button>
    </div>

    <!-- TABLE -->
    <table class="table table-striped table-sm table-bordered fs-6"
      style="border:2px solid #D3D3D3; border-collapse:collapse;">
      <?php if (count($data) === 0): ?>
        <tr>
          <td colspan="5" style="padding:10px; text-align:center; border:2px solid #D3D3D3;">No content to display</td>
        </tr>
      <?php else: ?>
        <thead>
          <tr>
            <!-- 1st column -->
            <th style="padding:10px; border:2px solid #D3D3D3;">
              <input type="checkbox" id="selectAll" />
            </th>

            <!-- 2nd column -->
            <th style="padding:10px; border:2px solid #D3D3D3;min-width: 150px !important;">
              <?php
              $queryParams = $_GET;
              $queryParams['sort'] = ($sortOption === 'first_name_asc') ? 'first_name_desc' : 'first_name_asc';
              $sortUrl = '?' . http_build_query($queryParams);
              if ($sortOption === 'first_name_asc') {
                $icons = '&nbsp;<i class="bi bi-caret-up-fill"></i>';
              } elseif ($sortOption === 'first_name_desc') {
                $icons = '&nbsp;<i class="bi bi-caret-down-fill mt-1"></i>';
              } else {
                if (empty($_GET)) {
                  // No query parameters — show default up icon
                  $icons = '&nbsp;<i class="bi bi-caret-up-fill"></i>';
                } else {
                  // Fallback (optional): show both icons if needed
                  $icons = '&nbsp;<div class="d-flex" style="width:15px;flex-direction:column;margin:0px;margin-top:-5px;">
                                  <i class="bi bi-caret-up-fill" style="margin:0px;"></i>
                                  <i class="bi bi-caret-down-fill" style="margin:0px;margin-top:-5px;"></i>
                                </div>';
                }
              }
              ?>
              <a href="<?= $sortUrl ?>" class="text-decoration-none text-primary d-flex load_ajax">
                <i class="bi-person me-1"></i>First Name <?= $icons ?>
              </a>
            </th>

            <!-- 3rd column -->
            <th style="padding:10px; border:2px solid #D3D3D3;min-width: 150px !important;">
              <?php
              $queryParams = $_GET;
              $queryParams['sort'] = ($sortOption === 'last_name_asc') ? 'last_name_desc' : 'last_name_asc';
              $sortUrl = '?' . http_build_query($queryParams);
              if ($sortOption === 'last_name_asc') {
                $icons = '&nbsp;<i class="bi bi-caret-up-fill"></i>';
              } elseif ($sortOption === 'last_name_desc') {
                $icons = '&nbsp;<i class="bi bi-caret-down-fill mt-1"></i>';
              } else {
                $icons = '&nbsp;<div class="d-flex" style="width:15px;flex-direction:column;margin:0px;margin-top:-5px;">
                                      <i class="bi bi-caret-up-fill" style="margin:0px;"></i>
                                      <i class="bi bi-caret-down-fill" style="margin:0px;margin-top:-5px;"></i>
                              </div>';
              }
              ?>
              <a href="<?= $sortUrl ?>" class="text-decoration-none text-primary d-flex load_ajax">
                <i class="bi-person me-1"></i>Last Name <?= $icons ?>
              </a>
            </th>

            <!-- 4th column -->
            <th class="text-primary" style="padding:10px; border:2px solid #D3D3D3;">
              <?php
              $queryParams = $_GET;
              $queryParams['sort'] = ($sortOption === 'email_asc') ? 'email_desc' : 'email_asc';
              $sortUrl = '?' . http_build_query($queryParams);
              if ($sortOption === 'email_asc') {
                $icons = '&nbsp;<i class="bi bi-caret-up-fill"></i>';
              } elseif ($sortOption === 'email_desc') {
                $icons = '&nbsp;<i class="bi bi-caret-down-fill mt-1"></i>';
              } else {
                $icons = '&nbsp;<div class="d-flex" style="width:15px;flex-direction:column;margin:0px;margin-top:-5px;">
                                    <i class="bi bi-caret-up-fill" style="margin:0px;"></i>
                                    <i class="bi bi-caret-down-fill" style="margin:0px;margin-top:-5px;"></i>
                                  </div>';
              }
              ?>
              <a href="<?= $sortUrl ?>" class="text-decoration-none text-primary d-flex load_ajax">
                <i class="bi-envelope me-1"></i>Email <?= $icons ?></i>
              </a>
            </th>
            <!-- 5th column -->
            <th class="text-primary" style="padding:10px; border:2px solid #D3D3D3;min-width: 120px !important;">
              <i class="bi bi-pc-display me-1"></i>Skills
            </th>
            <!-- 6th column -->
            <th class="text-primary" style="padding:10px; border:2px solid #D3D3D3;min-width: 120px !important;">
              <i class="bi-activity me-1"></i>Verified
            </th>
            <!-- 7th column -->
            <th class="text-primary" style="padding:10px; border:2px solid #D3D3D3;">
              <i class="bi bi-gear me-1"></i>Action
            </th>
          </tr>
        </thead>


        <tbody>
          <?php foreach ($data as $row): ?>
            <tr>
              <td style="padding:10px; border:2px solid #D3D3D3;">
                <input type="checkbox" class="selectSingle" value="<?= $row['id'] ?>">
              </td>
              <td style="padding:10px; border:2px solid #D3D3D3;">
                <?php echo htmlspecialchars($row['first_name']); ?>
              </td>
              <td style="padding:10px; border:2px solid #D3D3D3;">
                <?php echo htmlspecialchars($row['last_name']); ?>
              </td>
              <td style="padding:10px; border:2px solid #D3D3D3;"><?php echo htmlspecialchars($row['email']); ?></td>
              <td style="padding:10px; border:2px solid #D3D3D3;">
                <?php if (!empty($row['skills'])): ?>
                  <?php foreach ($row['skills'] as $skill): ?>
                    <span class="badge bg-secondary me-1"><?= htmlspecialchars($skill) ?></span>
                  <?php endforeach; ?>
                <?php else: ?>
                  No skills present
                <?php endif; ?>
              </td>
              <td style="padding:10px; border:2px solid #D3D3D3;">
                <?php if ($row['is_email_verified'] === '1'): ?>
                  <span class="badge bg-success p-2">Verified</span>
                <?php else: ?>
                  <span class="badge bg-danger p-2">Not Verified</span>
                <?php endif; ?>
              </td>
              <td style="padding:10px; border:2px solid #D3D3D3;min-width: 120px !important;">
                <button class="btn btn-sm delete-single" data-id="<?= $row['id'] ?>">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      <?php endif; ?>
    </table>
  </div>

  <!-- Pagination -->
  <div aria-label="Page navigation" style="width:100%;">
    <ul class="pagination justify-content-left flex-wrap">
      <?php if ($page > 1): ?>
        <li class="page-item">
          <?php
          $queryParams = $_GET;
          $queryParams['page'] = $page - 1;
          $prevUrl = '?' . http_build_query($queryParams);
          ?>
          <a class="page-link" href="<?= $prevUrl ?>"><i class="bi bi-chevron-double-left load_ajax"></i></a>
        </li>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
          <?php
          $queryParams = $_GET;
          $queryParams['page'] = $i;
          $pageUrl = '?' . http_build_query($queryParams);
          ?>
          <a class="page-link load_ajax" href="<?= $pageUrl ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <li class="page-item">
          <?php
          $queryParams = $_GET;
          $queryParams['page'] = $page + 1;
          $nextUrl = '?' . http_build_query($queryParams);
          ?>
          <a class="page-link load_ajax" href="<?= $nextUrl ?>"><i class="bi bi-chevron-double-right"></i></a>
        </li>
      <?php endif; ?>
    </ul>
  </div>

</main>

</div>
</div>
<script>
  $(function () {
    const $selectAllCheckbox = $('#selectAll');
    const $deleteSelectedBtn = $('#deleteSelected');
    const $singleCheckboxes = $('.selectSingle');

    function updateDeleteButtonState() {
      const anyChecked = $singleCheckboxes.is(':checked');
      $deleteSelectedBtn.prop('disabled', !anyChecked);
    }

    $selectAllCheckbox.on('change', function () {
      $singleCheckboxes.prop('checked', this.checked);
      updateDeleteButtonState();
    });

    $singleCheckboxes.on('change', updateDeleteButtonState);

    // SINGLE DELETE
    $('.delete-single').on('click', function () {
      const $row = $(this).closest('tr');              // Get the row of the clicked icon
      const $checkbox = $row.find('.selectSingle');    // Find the checkbox in that row
      $checkbox.prop('checked', true);                 // Auto-select it
      updateDeleteButtonState();                       // Update delete button state if needed

      const userId = $(this).data('id');

      setTimeout(() => {
        if (confirm("Are you sure you want to delete this customer?")) {
          const formData = new FormData();
          formData.append("id", userId);
          formData.append("csrfToken", "<?= isset($_SESSION['csrfToken']) ? $_SESSION['csrfToken'] : "" ?>");
          formData.append('function', 'DeleteUser');
          $.ajax({
            url: "front_ajax.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
              alert(response.message || "Deleted successfully.");
              location.reload();
            },
            error: function (xhr) {
              console.error("Status Code:", xhr.status);
              console.error("Response:", xhr.responseText);
              let errorResponse = {};
              try { errorResponse = JSON.parse(xhr.responseText); } catch (e) { }
              alert(errorResponse.message || 'Error deleting customer.');
            }
          });
        } else {
          $checkbox.prop('checked', false);                 // Auto-select it
          updateDeleteButtonState();                   // Update delete button state if needed        
        }
      }, 200);
    });

    // BULK DELETE
    $deleteSelectedBtn.on('click', function () {
      const selectedIds = $singleCheckboxes
        .filter(':checked')
        .map(function () { return this.value; })
        .get();

      if (selectedIds.length === 0) return;

      if (confirm(`Are you sure you want to delete ${selectedIds.length} selected customers?`)) {
        const formData = new FormData();
        selectedIds.forEach(id => formData.append("ids[]", id));

        // Append CSRF token
        formData.append("csrfToken", "<?= isset($_SESSION['csrfToken']) ? $_SESSION['csrfToken'] : '' ?>");
        formData.append('function', 'DeleteUser');

        $.ajax({
          url: "front_ajax.php",
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          success: function (response) {
            alert(response.message || "Selected customers deleted.");
            location.reload();
          },
          error: function (xhr) {
            console.error("Status Code:", xhr.status);
            console.error("Response:", xhr.responseText);
            let errorResponse = {};
            try { errorResponse = JSON.parse(xhr.responseText); } catch (e) { }
            alert(errorResponse.message || 'Error deleting customers.');
          }
        });
      } else {
        // Cancel → uncheck all and update button
        $singleCheckboxes.prop('checked', false);
        $selectAllCheckbox.prop('checked', false);
        updateDeleteButtonState();
      }
    });
  });
</script>
<?php require_once __DIR__ . "/footer.php"; ?>