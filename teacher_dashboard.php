<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "student_db");
$t_id = $_SESSION['teacher_username_id'] ?? ''; 

$t_stmt = $conn->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$t_stmt->bind_param("s", $t_id);
$t_stmt->execute();
$teacher_data = $t_stmt->get_result()->fetch_assoc();
$t_stmt->close();

$dept = $teacher_data['dept'] ?? '';
$subject = $teacher_data['subject'] ?? '';
$t_sem = intval($teacher_data['semester'] ?? 1);
$teacher_photo = $teacher_data['profile_photo'] ?? null;

// =========================================================================
// 🔒 ACTION: SUBJECT-ISOLATED ACCESS GRANT
// =========================================================================
if (isset($_POST['grant_subject_access'])) {
    $stu_usn = $_POST['approve_usn'];

    $check = $conn->prepare("SELECT id FROM academic_records WHERE student_usn = ? AND subject_name = ? AND semester = ?");
    $check->bind_param("ssi", $stu_usn, $subject, $t_sem);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $upd = $conn->prepare("UPDATE academic_records SET subject_status = 'Approved' WHERE student_usn = ? AND subject_name = ? AND semester = ?");
        $upd->bind_param("ssi", $stu_usn, $subject, $t_sem);
        $upd->execute();
        $upd->close();
    } else {
        $ins = $conn->prepare("INSERT INTO academic_records (student_usn, teacher_id, subject_name, semester, subject_status, ia_marks, attendance) VALUES (?, ?, ?, ?, 'Approved', 0, 0)");
        $ins->bind_param("sssi", $stu_usn, $t_id, $subject, $t_sem);
        $ins->execute();
        $ins->close();
    }

    echo "<script>alert('✔️ Access Granted! Student added to your active $subject roster.'); window.location.href='teacher_dashboard.php';</script>";
    exit();
}

// Handle Photo Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_teacher_photo'])) {
    if (!empty($teacher_photo) && file_exists($teacher_photo)) { unlink($teacher_photo); }
    $del_stmt = $conn->prepare("UPDATE teachers SET profile_photo = NULL WHERE teacher_id = ?");
    $del_stmt->bind_param("s", $t_id);
    $del_stmt->execute();
    $del_stmt->close();
    echo "<script>alert('Profile photo removed.'); window.location.href='teacher_dashboard.php';</script>";
    exit();
}

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher_profile'])) {
    $upd_name = trim($_POST['teacher_name']);
    $upd_subject = trim($_POST['teacher_subject']);
    $upd_sem = intval($_POST['teacher_semester']);
    
    if ($upd_subject !== $subject) {
        $sync = $conn->prepare("UPDATE academic_records SET subject_name = ? WHERE teacher_id = ? AND subject_name = ?");
        $sync->bind_param("sss", $upd_subject, $t_id, $subject);
        $sync->execute();
        $sync->close();
    }

    if (isset($_FILES['teacher_photo']) && $_FILES['teacher_photo']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['teacher_photo']['name'], PATHINFO_EXTENSION));
        $destination = "uploads/TEA_" . $t_id . "_" . time() . "." . $file_ext;
        if (move_uploaded_file($_FILES['teacher_photo']['tmp_name'], $destination)) {
            $upd_stmt = $conn->prepare("UPDATE teachers SET name=?, subject=?, semester=?, profile_photo=? WHERE teacher_id=?");
            $upd_stmt->bind_param("ssiss", $upd_name, $upd_subject, $upd_sem, $destination, $t_id);
        }
    } else {
        $upd_stmt = $conn->prepare("UPDATE teachers SET name=?, subject=?, semester=? WHERE teacher_id=?");
        $upd_stmt->bind_param("ssis", $upd_name, $upd_subject, $upd_sem, $t_id);
    }
    $upd_stmt->execute();
    $upd_stmt->close();
    echo "<script>alert('Profile modified successfully!'); window.location.href='teacher_dashboard.php';</script>";
    exit();
}

// Handle Marks Saving
if (isset($_POST['upload_marks'])) {
    $stu_usn = $_POST['student_usn'];
    $ia = intval($_POST['ia_marks']);
    $att = intval($_POST['attendance']);

    $upd = $conn->prepare("UPDATE academic_records SET ia_marks = ?, attendance = ? WHERE student_usn = ? AND subject_name = ? AND semester = ?");
    $upd->bind_param("iisss", $ia, $att, $stu_usn, $subject, $t_sem);
    $upd->execute();
    $upd->close();
    echo "<script>alert('Academic performance saved for $stu_usn!'); window.location.href='teacher_dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Faculty Dashboard — Academic Portal System">
    <title>Faculty Dashboard — Academic Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- ── Sticky Navigation ── -->
    <nav class="portal-nav" role="navigation" aria-label="Teacher portal navigation">
        <div class="nav-brand">
            <?php if (!empty($teacher_photo)): ?>
                <img src="<?php echo htmlspecialchars($teacher_photo); ?>"
                     class="nav-avatar" alt="Teacher profile photo">
            <?php else: ?>
                <div class="nav-avatar-placeholder" aria-hidden="true">👨‍🏫</div>
            <?php endif; ?>
            <div>
                <h1><?php echo htmlspecialchars($teacher_data['name']); ?></h1>
                <span><?php echo htmlspecialchars($subject); ?> &nbsp;·&nbsp; Semester <?php echo $t_sem; ?></span>
            </div>
        </div>
        <div class="nav-actions">
            <span class="badge badge-accent"><?php echo htmlspecialchars($teacher_data['dept'] ?? ''); ?></span>
            <a href="logout.php" class="btn-logout" aria-label="Logout from portal">⏻ Logout</a>
        </div>
    </nav>

    <main class="page-wrapper stack" role="main">

        <!-- ══ Row 1: Config Panel + Evaluation Stats ══ -->
        <div class="two-col-grid">

            <!-- Dashboard Config -->
            <div class="section-card card-accent-top">
                <h2 class="section-title">⚙️ Dashboard Config</h2>
                <form action="teacher_dashboard.php" method="POST" enctype="multipart/form-data" novalidate>
                    <div class="form-group">
                        <label for="conf_name">Full Name</label>
                        <input type="text" name="teacher_name" id="conf_name"
                               value="<?php echo htmlspecialchars($teacher_data['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="conf_subject">Subject</label>
                        <input type="text" name="teacher_subject" id="conf_subject"
                               value="<?php echo htmlspecialchars($subject); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="conf_sem">Target Semester</label>
                        <select name="teacher_semester" id="conf_sem" required>
                            <?php for($i=1;$i<=8;$i++) { $sel = ($t_sem == $i) ? 'selected' : ''; echo "<option value='$i' $sel>Semester $i</option>"; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="conf_photo">Update Profile Photo</label>
                        <input type="file" name="teacher_photo" id="conf_photo" accept="image/*">
                    </div>
                    <button type="submit" name="update_teacher_profile" class="btn-submit" aria-label="Save configuration">
                        Save Updates →
                    </button>
                </form>

                <?php if (!empty($teacher_photo)): ?>
                <form action="teacher_dashboard.php" method="POST" style="margin-top:10px;">
                    <button type="submit" name="delete_teacher_photo"
                            class="btn btn-danger btn-full btn-sm" aria-label="Remove profile photo">
                        🗑 Remove Profile Photo
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Evaluation Performance Stats -->
            <div class="section-card card-accent-green">
                <?php
                $calc = $conn->prepare("SELECT AVG(q1_punctuality) as a1, AVG(q2_syllabus) as a2, AVG(q3_communication) as a3, AVG(q4_explanation) as a4, COUNT(id) as total_forms FROM feedback WHERE teacher_id = ? AND subject_name = ? AND semester = ?");
                $calc->bind_param("ssi", $t_id, $subject, $t_sem);
                $calc->execute();
                $m = $calc->get_result()->fetch_assoc();
                $calc->close();

                $count = $m['total_forms'] ?? 0;
                $avg   = $count > 0 ? ($m['a1'] + $m['a2'] + $m['a3'] + $m['a4']) / 4 : 0;

                $a1_pct = $count > 0 ? round(($m['a1'] / 5) * 100) : 0;
                $a2_pct = $count > 0 ? round(($m['a2'] / 5) * 100) : 0;
                $a3_pct = $count > 0 ? round(($m['a3'] / 5) * 100) : 0;
                $a4_pct = $count > 0 ? round(($m['a4'] / 5) * 100) : 0;
                ?>

                <h2 class="section-title">📊 Course Evaluation Performance</h2>

                <div class="score-panel">
                    <span class="score-number" data-target="<?php echo number_format($avg, 2); ?>">
                        <?php echo number_format($avg, 2); ?>
                    </span>
                    <div class="score-label">Overall Rating / 5.00 ⭐</div>
                    <div class="score-sub">
                        <?php echo $count; ?> form<?php echo $count !== 1 ? 's' : ''; ?> submitted for
                        <strong><?php echo htmlspecialchars($subject); ?></strong>
                    </div>
                </div>

                <div style="margin-top:18px;">
                    <div class="stat-bar-wrap">
                        <div class="stat-bar-label">
                            <span>Punctuality</span>
                            <span><?php echo number_format($m['a1'] ?? 0, 1); ?>/5</span>
                        </div>
                        <div class="stat-bar-track">
                            <div class="stat-bar-fill" data-pct="<?php echo $a1_pct; ?>"></div>
                        </div>
                    </div>
                    <div class="stat-bar-wrap">
                        <div class="stat-bar-label">
                            <span>Syllabus Coverage</span>
                            <span><?php echo number_format($m['a2'] ?? 0, 1); ?>/5</span>
                        </div>
                        <div class="stat-bar-track">
                            <div class="stat-bar-fill" data-pct="<?php echo $a2_pct; ?>"></div>
                        </div>
                    </div>
                    <div class="stat-bar-wrap">
                        <div class="stat-bar-label">
                            <span>Communication</span>
                            <span><?php echo number_format($m['a3'] ?? 0, 1); ?>/5</span>
                        </div>
                        <div class="stat-bar-track">
                            <div class="stat-bar-fill" data-pct="<?php echo $a3_pct; ?>"></div>
                        </div>
                    </div>
                    <div class="stat-bar-wrap">
                        <div class="stat-bar-label">
                            <span>Conceptual Clarity</span>
                            <span><?php echo number_format($m['a4'] ?? 0, 1); ?>/5</span>
                        </div>
                        <div class="stat-bar-track">
                            <div class="stat-bar-fill" data-pct="<?php echo $a4_pct; ?>"></div>
                        </div>
                    </div>
                </div>

                <h3 style="margin:18px 0 10px;font-size:0.88rem;color:var(--text-secondary);">💬 Written Remarks</h3>
                <div class="comment-feed">
                    <?php
                    $inbox = $conn->prepare("SELECT student_usn, additional_comments FROM feedback WHERE teacher_id = ? AND subject_name = ? AND semester = ? ORDER BY id DESC");
                    $inbox->bind_param("ssi", $t_id, $subject, $t_sem);
                    $inbox->execute();
                    $res = $inbox->get_result();
                    $any_comments = false;
                    while ($f = $res->fetch_assoc()) {
                        if (!empty($f['additional_comments'])) {
                            $any_comments = true;
                            echo "<div class='comment-bubble'>";
                            echo "<div class='comment-usn'>USN: " . htmlspecialchars($f['student_usn']) . "</div>";
                            echo "<div class='comment-text'>\"" . htmlspecialchars($f['additional_comments']) . "\"</div>";
                            echo "</div>";
                        }
                    }
                    if (!$any_comments) {
                        echo "<p style='color:var(--text-muted);font-size:0.84rem;font-style:italic;'>No written remarks submitted yet.</p>";
                    }
                    $inbox->close();
                    ?>
                </div>
            </div>

        </div>

        <!-- ══ Row 2: Pending Access Control ══ -->
        <div class="section-card card-accent-gold">
            <h2 class="section-title">
                ⏳ Enforce Subject Access Control
                <span class="badge badge-warning" style="margin-left:auto;">Semester <?php echo $t_sem; ?></span>
            </h2>
            <p style="font-size:0.84rem;color:var(--text-secondary);margin-bottom:16px;">
                Students below have not yet been approved to access your course
                <strong><?php echo htmlspecialchars($subject); ?></strong>.
            </p>

            <table class="data-table" role="grid" aria-label="Pending students table">
                <thead>
                    <tr>
                        <th scope="col">USN / Roll Number</th>
                        <th scope="col">Student Full Name</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pending_stmt = $conn->prepare("
                        SELECT s.roll_no, s.name
                        FROM students s
                        WHERE s.semester = ?
                          AND s.roll_no LIKE CONCAT('%', ?, '%')
                          AND s.roll_no NOT IN (
                              SELECT r.student_usn
                              FROM academic_records r
                              WHERE r.subject_name = ?
                                AND r.subject_status = 'Approved'
                                AND r.semester = ?
                          )
                    ");
                    $pending_stmt->bind_param("issi", $t_sem, $dept, $subject, $t_sem);
                    $pending_stmt->execute();
                    $p_records = $pending_stmt->get_result();

                    if ($p_records->num_rows > 0) {
                        while($p_row = $p_records->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($p_row['roll_no']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($p_row['name'] ?? 'Unfilled Name') . "</td>";
                            echo "<td>
                                    <form action='teacher_dashboard.php' method='POST' style='margin:0;'>
                                        <input type='hidden' name='approve_usn' value='" . htmlspecialchars($p_row['roll_no']) . "'>
                                        <button type='submit' name='grant_subject_access'
                                                class='btn btn-success btn-sm'
                                                aria-label='Grant course access to " . htmlspecialchars($p_row['roll_no']) . "'>
                                            ✔ Grant Access
                                        </button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center;color:var(--text-muted);padding:24px;font-style:italic;'>All students are approved for " . htmlspecialchars($subject) . ".</td></tr>";
                    }
                    $pending_stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <!-- ══ Row 3: Active Class Roster ══ -->
        <div class="section-card">
            <h2 class="section-title">
                📋 Active Class Roster
                <span class="badge badge-primary" style="margin-left:auto;"><?php echo htmlspecialchars($subject); ?></span>
            </h2>

            <table class="data-table" role="grid" aria-label="Class roster table">
                <thead>
                    <tr>
                        <th scope="col">USN / Roll Number</th>
                        <th scope="col">Student Name</th>
                        <th scope="col">IA Score</th>
                        <th scope="col">Attendance</th>
                        <th scope="col">Update Marks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $roster = $conn->prepare("SELECT s.roll_no, s.name, r.ia_marks, r.attendance
                                              FROM students s
                                              JOIN academic_records r ON s.roll_no = r.student_usn
                                              WHERE r.subject_name = ? AND r.subject_status = 'Approved' AND r.semester = ? AND s.roll_no LIKE CONCAT('%', ?, '%')");
                    $roster->bind_param("sis", $subject, $t_sem, $dept);
                    $roster->execute();
                    $students = $roster->get_result();

                    if ($students->num_rows > 0) {
                        while($row = $students->fetch_assoc()) {
                            $att_cls = ($row['attendance'] < 75) ? 'attendance-bad' : 'attendance-good';
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['roll_no']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . $row['ia_marks'] . " <span style='color:var(--text-muted);font-size:0.78rem;'>/ 40</span></td>";
                            echo "<td class='$att_cls'>" . $row['attendance'] . "%</td>";
                            echo "<td>
                                    <form action='teacher_dashboard.php' method='POST'
                                          style='display:flex;gap:7px;margin:0;align-items:center;'>
                                        <input type='hidden' name='student_usn' value='" . htmlspecialchars($row['roll_no']) . "'>
                                        <input type='number' name='ia_marks' value='" . $row['ia_marks'] . "'
                                               max='40' min='0' required aria-label='IA Marks' style='width:60px;'>
                                        <input type='number' name='attendance' value='" . $row['attendance'] . "'
                                               max='100' min='0' required aria-label='Attendance %' style='width:68px;'>
                                        <button type='submit' name='upload_marks'
                                                class='btn btn-success btn-sm'
                                                aria-label='Save marks for " . htmlspecialchars($row['roll_no']) . "'>
                                            Save
                                        </button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center;color:var(--text-muted);padding:28px;font-style:italic;'>No approved students on your $subject tracking sheet yet.</td></tr>";
                    }
                    $roster->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>

    </main>

    <script src="portal.js"></script>
</body>
</html>