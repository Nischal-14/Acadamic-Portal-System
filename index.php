<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Academic Portal — Student & Faculty login gateway for the Academic Management System.">
    <title>Academic Portal — Gateway</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

    <div class="login-wrapper">

        <!-- Hero Branding -->
        <div class="login-hero">
            <div class="portal-logo" aria-hidden="true">🎓</div>
            <h1 class="gradient-text">Academic Portal System</h1>
            <p>Select your role gateway to access your academic environment</p>
        </div>

        <div class="login-container">

            <!-- ── Student Login Card ── -->
            <div class="login-card" id="student-login-card">
                <div class="login-card-header">
                    <div class="login-card-icon student-icon" aria-hidden="true">🎓</div>
                    <div>
                        <h2>Student Login
                            <span>Access your profile &amp; evaluations</span>
                        </h2>
                    </div>
                </div>

                <form action="login_process.php?role=student" method="POST" novalidate>
                    <div class="form-group">
                        <label for="login_dept">Department</label>
                        <select name="login_dept" id="login_dept" required aria-required="true">
                            <option value="">— Choose Branch —</option>
                            <option value="CS">Computer Science &amp; Engineering (CSE)</option>
                            <option value="EC">Electronics &amp; Communication (ECE)</option>
                            <option value="IS">Information Science &amp; Engineering (ISE)</option>
                            <option value="ME">Mechanical Engineering (ME)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="login_semester">Semester</label>
                        <select name="login_semester" id="login_semester" required aria-required="true">
                            <option value="">— Choose Semester —</option>
                            <?php for($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="student_usn">Student USN</label>
                        <input type="text" name="username" id="student_usn" required
                               placeholder="e.g., 4AI24CS001" autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="student_pass">Password (Numbers Only)</label>
                        <input type="text" name="password" id="student_pass" required
                               pattern="[0-9]+" inputmode="numeric" placeholder="Default: 12345">
                    </div>

                    <button type="submit" class="btn-submit" aria-label="Access Student Portal">
                        Access Portal →
                    </button>
                    <a href="student_register.php" class="login-link">New student? Register here</a>
                </form>
            </div>

            <!-- ── Teacher Login Card ── -->
            <div class="login-card" id="teacher-login-card">
                <div class="login-card-header">
                    <div class="login-card-icon teacher-icon" aria-hidden="true">👨‍🏫</div>
                    <div>
                        <h2>Faculty Login
                            <span>Access your teaching console</span>
                        </h2>
                    </div>
                </div>

                <form action="login_process.php?role=teacher" method="POST" novalidate>
                    <div class="form-group">
                        <label for="teacher_username">Faculty ID / Username</label>
                        <input type="text" name="username" id="teacher_username" required
                               placeholder="e.g., CS01, EC01" autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="teacher_pass">Password (Numbers Only)</label>
                        <input type="text" name="password" id="teacher_pass" required
                               pattern="[0-9]+" inputmode="numeric" placeholder="Default: 789123">
                    </div>

                    <button type="submit" class="btn-submit" aria-label="Access Teacher Console">
                        Access Console →
                    </button>
                    <a href="teacher_register.php" class="login-link">New faculty? Register here</a>
                </form>
            </div>

        </div>
    </div>

    <script src="portal.js"></script>
</body>
</html>