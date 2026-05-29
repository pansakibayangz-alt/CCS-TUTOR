<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'INSTRUCTOR') exit;

$instructor_id = $_SESSION['instructor_id'] ?? null;
if (!$instructor_id) exit;

$course = $_GET['course'] ?? '';
$lesson = $_GET['lesson'] ?? '';
$year = $_GET['year'] ?? '';
$block = $_GET['block'] ?? '';

// Fetch pretests
$sql = "SELECT p.*, c.course_name, l.lesson_title 
        FROM pretests p
        JOIN courses c ON p.course_id=c.course_id
        JOIN lessons l ON p.lesson_id=l.lesson_id
        WHERE p.instructor_id=?";
$params = [$instructor_id];

if ($course) { $sql .= " AND c.course_name LIKE ?"; $params[] = "%$course%"; }
if ($lesson) { $sql .= " AND l.lesson_title LIKE ?"; $params[] = "%$lesson%"; }
if ($year) { $sql .= " AND p.year_level=?"; $params[] = $year; }
if ($block) { $sql .= " AND p.block=?"; $params[] = $block; }

$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pretests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$pretests) {
    echo "<p class='text-white'>No pre-tests found.</p>";
    exit;
}

foreach ($pretests as $p) {
    echo "<div class='mb-4'>";
    echo "<h5>Year {$p['year_level']} Block {$p['block']}</h5>";
    echo "<p>Course: {$p['course_name']} &nbsp; Lesson: {$p['lesson_title']}</p>";

    $itemsStmt = $pdo->prepare("SELECT * FROM pretest_items WHERE pretest_id=? ORDER BY item_no");
    $itemsStmt->execute([$p['pretest_id']]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Table with data-pretest-type
    echo "<table class='table table-sm table-light-custom text-white' data-pretest-type='{$p['pretest_type']}'>";
    echo "<thead><tr>
            <th>Item No</th>
            <th>Description</th>";
    if ($p['pretest_type'] === 'MULTIPLE CHOICE') echo "<th>Options</th>";
    echo "<th>Answer</th><th>Item Analysis</th><th>Actions</th></tr></thead><tbody>";

    foreach ($items as $item) {
        echo "<tr data-item-id='{$item['item_id']}'>
            <td>{$item['item_no']}</td>
            <td><input type='text' class='form-control input-light desc-field' value='".htmlspecialchars($item['question'])."' readonly></td>";

        if ($p['pretest_type'] === 'MULTIPLE CHOICE') {
            $options = json_decode($item['options'], true);
            $optionsStr = '';
            if ($options) {
                foreach ($options as $k=>$v) $optionsStr .= "$k: $v; ";
            }
            echo "<td><input type='text' class='form-control input-light options-field' value='".htmlspecialchars($optionsStr)."' readonly></td>";
        }

        echo "<td><input type='text' class='form-control input-light answer-field' value='".htmlspecialchars($item['answer'])."' readonly></td>";

        $analysis = json_decode($item['item_analysis'], true);
        $correct = $analysis['correct'] ?? 0;
        $wrong = $analysis['wrong'] ?? 0;
        echo "<td>{$correct} Correct / {$wrong} Wrong</td>";

        // Actions: initially Edit + Delete
        echo "<td>
                <button type='button' class='btn btn-sm btn-warning edit-item me-1'>Edit</button>
                <button type='button' class='btn btn-sm btn-danger delete-item'>Delete</button>
              </td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "<button type='button' class='btn btn-sm btn-primary add-item mt-2' data-pretest-id='{$p['pretest_id']}' data-pretest-type='{$p['pretest_type']}'>Add Item</button>";
    echo "</div>";
}
?>

<script>
// ======================= JS for Edit / Save / Delete / Add =======================

// Delegated Event Binding
function initPretestTable() {
    document.querySelectorAll('table').forEach(table => {
        const type = table.dataset.pretestType;

        // Edit
        table.querySelectorAll('.edit-item').forEach(btn => {
            btn.onclick = function() {
                const row = this.closest('tr');
                row.querySelectorAll('input').forEach(input => input.removeAttribute('readonly'));

                // Toggle buttons
                row.querySelector('.edit-item').style.display = 'none';
                row.querySelector('.delete-item').style.display = 'none';

                const actionsCell = row.cells[row.cells.length-1];
                const saveBtn = document.createElement('button');
                saveBtn.className = 'btn btn-sm btn-success save-item me-1';
                saveBtn.textContent = 'Save';
                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'btn btn-sm btn-secondary cancel-item';
                cancelBtn.textContent = 'Cancel';
                actionsCell.appendChild(saveBtn);
                actionsCell.appendChild(cancelBtn);

                saveBtn.onclick = function() {
                    const item_id = row.dataset.itemId;
                    const question = row.querySelector('.desc-field').value;
                    const answer = row.querySelector('.answer-field').value;
                    const options = row.querySelector('.options-field') ? row.querySelector('.options-field').value : null;

                    fetch('update_pretest_item.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({item_id, question, answer, options})
                    }).then(res=>res.json()).then(data=>{
                        if(data.success){
                            alert('Saved!');
                            row.querySelectorAll('input').forEach(input=>input.setAttribute('readonly', true));
                            saveBtn.remove(); cancelBtn.remove();
                            row.querySelector('.edit-item').style.display = '';
                            row.querySelector('.delete-item').style.display = '';
                        } else alert('Error saving!');
                    });
                };

                cancelBtn.onclick = function() {
                    fetchExistingPretests(); // reload table to cancel changes
                };
            };
        });

        // Delete
        table.querySelectorAll('.delete-item').forEach(btn=>{
            btn.onclick = function(){
                if(!confirm('Delete this item?')) return;
                const row = this.closest('tr');
                const item_id = row.dataset.itemId;
                fetch('delete_pretest_item.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({item_id})
                }).then(res=>res.json()).then(data=>{
                    if(data.success){
                        row.remove();
                        table.querySelectorAll('tbody tr').forEach((tr, idx)=>tr.cells[0].textContent=idx+1);
                    } else alert('Error deleting item');
                });
            };
        });
    });

    // Add Item
    document.querySelectorAll('.add-item').forEach(btn=>{
        btn.onclick = function(){
            const pretestId = this.dataset.pretestId;
            const type = this.dataset.pretestType;
            const table = this.previousElementSibling;
            const tbody = table.querySelector('tbody');
            const itemNo = tbody.children.length + 1;

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${itemNo}</td>
                <td><input type='text' class='form-control input-light desc-field'></td>
                ${type==='MULTIPLE CHOICE' ? "<td><input type='text' class='form-control input-light options-field'></td>":""}
                <td><input type='text' class='form-control input-light answer-field'></td>
                <td>0 Correct / 0 Wrong</td>
                <td>
                    <button type='button' class='btn btn-sm btn-success save-new-item me-1'>Save</button>
                    <button type='button' class='btn btn-sm btn-secondary cancel-new-item'>Cancel</button>
                </td>
            `;
            tbody.appendChild(newRow);

            newRow.querySelector('.save-new-item').onclick = function(){
                const question = newRow.querySelector('.desc-field').value;
                const answer = newRow.querySelector('.answer-field').value;
                const options = newRow.querySelector('.options-field') ? newRow.querySelector('.options-field').value : null;

                fetch('add_pretest_item.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({pretest_id: pretestId, question, answer, options})
                }).then(res=>res.json()).then(data=>{
                    if(data.success){
                        newRow.dataset.itemId = data.item_id;
                        alert('Item added!');
                        const actionsCell = newRow.cells[newRow.cells.length-1];
                        actionsCell.innerHTML = "<button type='button' class='btn btn-sm btn-warning edit-item me-1'>Edit</button><button type='button' class='btn btn-sm btn-danger delete-item'>Delete</button>";
                        initPretestTable();
                    } else alert('Error adding item');
                });
            };

            newRow.querySelector('.cancel-new-item').onclick = function(){
                newRow.remove();
                tbody.querySelectorAll('tr').forEach((tr, idx)=>tr.cells[0].textContent=idx+1);
            };
        };
    });
}

// Initial binding
initPretestTable();
</script>
