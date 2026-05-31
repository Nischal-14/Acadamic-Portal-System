<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "student_db");
$student_id = $_SESSION['student_id'];
$student_usn = $_SESSION['roll_no'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_photo'])) {
    $photo_stmt = $conn->prepare("SELECT profile_photo FROM students WHERE id = ?");
    $photo_stmt->bind_param("i", $student_id);
    $photo_stmt->execute();
    $photo_data = $photo_stmt->get_result()->fetch_assoc();
    $photo_stmt->close();

    if (!empty($photo_data['profile_photo']) && file_exists($photo_data['profile_photo'])) { unlink($photo_data['profile_photo']); }
    $del_stmt = $conn->prepare("UPDATE students SET profile_photo = NULL WHERE id = ?");
    $del_stmt->bind_param("i", $student_id);
    $del_stmt->execute();
    $del_stmt->close();
    echo "<script>alert('Your profile photo has been completely removed.'); window.location.href='student_profile.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $semester = intval($_POST['semester']);
    
    // Fetch current details to check for semester change and lock course
    $cur_stmt = $conn->prepare("SELECT semester, course FROM students WHERE id = ?");
    $cur_stmt->bind_param("i", $student_id);
    $cur_stmt->execute();
    $cur_data = $cur_stmt->get_result()->fetch_assoc();
    $old_semester = intval($cur_data['semester']);
    $old_course = trim($cur_data['course'] ?? '');
    $cur_stmt->close();
    
    // Lock course modification once set
    $course = (!empty($old_course)) ? $old_course : trim($_POST['course']);
    
    // Handle semester change: delete old records so they disappear from old teacher's dashboard
    if ($old_semester !== $semester && $old_semester > 0) {
        // 1. Delete old performance/academic records
        $del_records = $conn->prepare("DELETE FROM academic_records WHERE student_usn = ? AND semester = ?");
        $del_records->bind_param("si", $student_usn, $old_semester);
        $del_records->execute();
        $del_records->close();
        
        // 2. Delete old feedback forms submitted during that semester
        $del_feedback = $conn->prepare("DELETE FROM feedback WHERE student_usn = ? AND semester = ?");
        $del_feedback->bind_param("si", $student_usn, $old_semester);
        $del_feedback->execute();
        $del_feedback->close();
    }

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $destination = "uploads/STU_" . $student_usn . "_" . time() . "." . $file_ext;
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
            $update_stmt = $conn->prepare("UPDATE students SET name=?, course=?, email=?, profile_photo=?, semester=? WHERE id=?");
            $update_stmt->bind_param("ssssii", $name, $course, $email, $destination, $semester, $student_id);
        }
    } else {
        $update_stmt = $conn->prepare("UPDATE students SET name=?, course=?, email=?, semester=? WHERE id=?");
        $update_stmt->bind_param("sssii", $name, $course, $email, $semester, $student_id);
    }
    $update_stmt->execute();
    $update_stmt->close();
    echo "<script>alert('Your profile has been updated! Any semester changes have automatically moved your portal to the correct faculty.'); window.location.href='student_profile.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $t_id = $_POST['target_teacher_id'];
    $sub_name = $_POST['target_subject_name'];
    $s_sem = intval($_POST['target_semester']);
    $q1 = intval($_POST['q1']); 
    $q2 = intval($_POST['q2']); 
    $q3 = intval($_POST['q3']); 
    $q4 = intval($_POST['q4']);
    $comments = trim($_POST['additional_comments']);

    $feed_stmt = $conn->prepare("INSERT INTO feedback (student_usn, teacher_id, subject_name, q1_punctuality, q2_syllabus, q3_communication, q4_explanation, additional_comments, semester) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $feed_stmt->bind_param("sssiiiisi", $student_usn, $t_id, $sub_name, $q1, $q2, $q3, $q4, $comments, $s_sem);
    
    if ($feed_stmt->execute()) {
        echo "<script>alert('Thank you! Your feedback has been securely logged.'); window.location.href='student_profile.php';</script>";
        exit();
    }
    $feed_stmt->close();
}

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$s_curr_sem = intval($data['semester'] ?? 1);
$stmt->close();

// =========================================================================
// 🔍 🆕 VALIDATION LOCK: CHECK IF PROFILE RECORDS ARE FILLED OUT
// =========================================================================
$is_profile_complete = true;
if (
    empty($data['name']) || 
    empty($data['course']) || 
    empty($data['email']) || 
    empty($data['profile_photo']) || 
    trim($data['name']) === "" || 
    trim($data['course']) === "" || 
    trim($data['email']) === ""
) {
    $is_profile_complete = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Student Academic Dashboard — Profile, Report Card & Faculty Evaluation">
    <title>Student Portal — Academic Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- ── Sticky Navigation ── -->
    <nav class="portal-nav" role="navigation" aria-label="Student portal navigation">
        <div class="nav-brand">
            <?php if (!empty($data['profile_photo'])): ?>
                <img src="<?php echo htmlspecialchars($data['profile_photo']); ?>"
                     class="nav-avatar" alt="Your profile photo">
            <?php else: ?>
                <div class="nav-avatar-placeholder" aria-hidden="true">🎓</div>
            <?php endif; ?>
            <div>
                <h1>Student Workspace</h1>
                <span>USN: <?php echo htmlspecialchars($student_usn); ?> &nbsp;·&nbsp; Semester <?php echo $s_curr_sem; ?></span>
            </div>
        </div>
        <div class="nav-actions">
            <span class="badge badge-primary">Sem <?php echo $s_curr_sem; ?></span>
            <a href="logout.php" class="btn-logout" aria-label="Logout from portal">⏻ Logout</a>
        </div>
    </nav>

    <main class="page-wrapper stack" role="main">

        <!-- ══ Row 1: Profile Panel + Report Card ══ -->
        <div class="two-col-grid">

            <!-- Profile Edit Panel -->
            <div class="section-card card-accent-top">
                <h2 class="section-title">👤 Personal Profile Records</h2>

                <?php if (!empty($data['profile_photo'])): ?>
                    <div class="photo-preview-wrap">
                        <img src="<?php echo htmlspecialchars($data['profile_photo']); ?>"
                             class="photo-preview" alt="Current profile photo">
                        <form action="student_profile.php" method="POST">
                            <button type="submit" name="delete_photo"
                                    class="btn btn-danger btn-sm"
                                    aria-label="Remove profile photo">
                                🗑 Remove Photo
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <form action="student_profile.php" method="POST" enctype="multipart/form-data" novalidate>
                    <div class="form-group">
                        <label>
                            <?php echo empty($data['profile_photo'])
                                ? '📷 Upload Profile Photo (Required)'
                                : '📷 Change Profile Photo (Optional)'; ?>
                        </label>
                        <input type="file" name="profile_photo" accept="image/*"
                               <?php echo empty($data['profile_photo']) ? 'required' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="stu_name">Full Name</label>
                        <input type="text" name="name" id="stu_name"
                               value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>"
                               required placeholder="Your full name">
                    </div>
                    <div class="form-group">
                        <label for="stu_course">Branch / Course</label>
                        <input type="text" name="course" id="stu_course"
                               value="<?php echo htmlspecialchars($data['course'] ?? ''); ?>"
                               required placeholder="e.g., Computer Science"
                               <?php echo !empty($data['course']) ? 'readonly style="background-color: rgba(255,255,255,0.05); cursor: not-allowed; opacity: 0.7;" title="Course cannot be changed once set"' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="stu_email">Email Address</label>
                        <input type="email" name="email" id="stu_email"
                               value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>"
                               required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label for="stu_sem">Current Active Semester</label>
                        <select name="semester" id="stu_sem" required>
                            <?php for($i=1; $i<=8; $i++) { $s = ($s_curr_sem == $i) ? 'selected' : ''; echo "<option value='$i' $s>Semester $i</option>"; } ?>
                        </select>
                    </div>
                    <button type="submit" name="update_profile" class="btn-submit" aria-label="Update profile records">
                        Update Records →
                    </button>
                </form>
            </div>

            <!-- Academic Report Card -->
            <div class="section-card">
                <h2 class="section-title">
                    📊 Performance Report Card
                    <span class="badge badge-primary" style="margin-left:auto;">Semester <?php echo $s_curr_sem; ?></span>
                </h2>

                <table class="data-table" role="grid" aria-label="Academic performance table">
                    <thead>
                        <tr>
                            <th scope="col">Subject</th>
                            <th scope="col">IA Marks</th>
                            <th scope="col">Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $marks_stmt = $conn->prepare("SELECT subject_name, ia_marks, attendance, subject_status FROM academic_records WHERE student_usn = ? AND semester = ?");
                        $marks_stmt->bind_param("si", $student_usn, $s_curr_sem);
                        $marks_stmt->execute();
                        $records = $marks_stmt->get_result();

                        if ($records->num_rows > 0) {
                            while($row = $records->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars($row['subject_name']) . "</strong></td>";

                                if ($row['subject_status'] === 'Pending') {
                                    echo "<td colspan='2'><span class='badge badge-warning'>⏳ Pending Instructor Approval</span></td>";
                                } else {
                                    echo "<td>" . $row['ia_marks'] . " <span style='color:var(--text-muted);font-size:0.78rem;'>/ 40</span></td>";
                                    $cls = ($row['attendance'] < 75) ? 'attendance-bad' : 'attendance-good';
                                    echo "<td class='$cls'>" . $row['attendance'] . "%</td>";
                                }
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center;color:var(--text-muted);padding:30px;font-style:italic;'>No subject records mapped for Semester $s_curr_sem yet.</td></tr>";
                        }
                        $marks_stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- ══ Row 2: Faculty Evaluation Dashboard ══ -->
        <div class="section-card">
            <h2 class="section-title">✍️ Faculty Evaluation Dashboard</h2>

            <div class="stack" style="gap:14px;">

                <?php if (!$is_profile_complete): ?>
                    <div class="alert-panel alert-danger" role="alert">
                        <span style="font-size:1.2rem;">🛑</span>
                        <span>Evaluation Access Locked — Complete your Personal Profile (photo, name, course &amp; email) and save to unlock the faculty evaluation portal.</span>
                    </div>
                <?php else: ?>

                    <?php
                    $faculty_stmt = $conn->prepare("SELECT teacher_id, name, subject FROM teachers WHERE semester = ?");
                    $faculty_stmt->bind_param("i", $s_curr_sem);
                    $faculty_stmt->execute();
                    $faculty_list = $faculty_stmt->get_result();

                    if ($faculty_list->num_rows > 0) {
                        while ($f_row = $faculty_list->fetch_assoc()) {
                            $current_sub = $f_row['subject'];

                            $status_stmt = $conn->prepare("SELECT subject_status FROM academic_records WHERE student_usn = ? AND subject_name = ? AND semester = ?");
                            $status_stmt->bind_param("ssi", $student_usn, $current_sub, $s_curr_sem);
                            $status_stmt->execute();
                            $status_res = $status_stmt->get_result();

                            $is_approved = false;
                            if ($status_res->num_rows > 0) {
                                $status_row = $status_res->fetch_assoc();
                                if ($status_row['subject_status'] === 'Approved') {
                                    $is_approved = true;
                                }
                            }
                            $status_stmt->close();
                    ?>

                    <div class="eval-card">
                        <div class="eval-card-header">
                            <div>
                                <h3>📚 <?php echo htmlspecialchars($current_sub); ?></h3>
                                <p class="faculty-label">Faculty: <strong><?php echo htmlspecialchars($f_row['name']); ?></strong></p>
                            </div>
                            <?php if ($is_approved): ?>
                                <span class="badge badge-success">✓ Approved</span>
                            <?php else: ?>
                                <span class="badge badge-warning">⏳ Pending</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($is_approved): ?>
                            <form action="student_profile.php" method="POST" novalidate>
                                <input type="hidden" name="target_teacher_id" value="<?php echo $f_row['teacher_id']; ?>">
                                <input type="hidden" name="target_subject_name" value="<?php echo htmlspecialchars($current_sub); ?>">
                                <input type="hidden" name="target_semester" value="<?php echo $s_curr_sem; ?>">

                                <div class="eval-questions">
                                    <div class="eval-question">
                                        <label>1. Punctuality</label>
                                        <select name="q1">
                                            <option value="5">5⭐ Excellent</option>
                                            <option value="4">4⭐ Very Good</option>
                                            <option value="3">3⭐ Average</option>
                                            <option value="2">2⭐ Poor</option>
                                            <option value="1">1⭐ Unacceptable</option>
                                        </select>
                                    </div>
                                    <div class="eval-question">
                                        <label>2. Syllabus Coverage</label>
                                        <select name="q2">
                                            <option value="5">5⭐ Excellent</option>
                                            <option value="4">4⭐ Very Good</option>
                                            <option value="3">3⭐ Average</option>
                                            <option value="2">2⭐ Poor</option>
                                            <option value="1">1⭐ Unacceptable</option>
                                        </select>
                                    </div>
                                    <div class="eval-question">
                                        <label>3. Communication</label>
                                        <select name="q3">
                                            <option value="5">5⭐ Excellent</option>
                                            <option value="4">4⭐ Very Good</option>
                                            <option value="3">3⭐ Average</option>
                                            <option value="2">2⭐ Poor</option>
                                            <option value="1">1⭐ Unacceptable</option>
                                        </select>
                                    </div>
                                    <div class="eval-question">
                                        <label>4. Conceptual Clarity</label>
                                        <select name="q4">
                                            <option value="5">5⭐ Excellent</option>
                                            <option value="4">4⭐ Very Good</option>
                                            <option value="3">3⭐ Average</option>
                                            <option value="2">2⭐ Poor</option>
                                            <option value="1">1⭐ Unacceptable</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="eval-comments-row">
                                    <input type="text" name="additional_comments"
                                           placeholder="Optional remarks for this instructor...">
                                    <button type="submit" name="submit_feedback"
                                            class="btn btn-accent" aria-label="Submit evaluation form">
                                        Submit
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert-panel alert-warning" role="alert">
                                <span>🔒</span>
                                <span>Feedback locked — Access for <strong><?php echo htmlspecialchars($current_sub); ?></strong> is pending approval from the instructor.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php
                        }
                    } else {
                        echo "<p style='color:var(--text-muted);font-style:italic;padding:12px 0;'>No instructors are mapped onto Semester $s_curr_sem yet.</p>";
                    }
                    $faculty_stmt->close();
                endif;
                $conn->close();
                ?>

            </div>
        </div>

    </main>

    <script src="portal.js"></script>
</body>
</html>