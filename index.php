<?php
// index.php
require_once 'includes/auth.php';
require_once 'includes/header.php';
?>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>


<!-- HERO -->
<section id="home">
    <div class="hero-glow"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-badge fade-up">My Portfolio</div>
                <h1 class="hero-title fade-up delay-1">
                    Kimsreng <span class="accent">Song</span>
                </h1>
                <p class="hero-subtitle fade-up delay-2">
                    I build modern, responsive and scalable web applications with MERN stack and bring ideas to life
                    on the web.
                </p>
                <div class="d-flex gap-3 flex-wrap mb-4 fade-up delay-3">
                    <a href="#projects" class="btn btn-accent">
                        View My Work <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                    <a href="#contact" class="btn btn-outline-accent">Contact Me</a>
                </div>
                <div class="hero-socials d-flex gap-2 fade-up delay-4">
                    <a href="https://github.com/songkimsreng001-hash"><i class="bi bi-github"></i></a>
                    <a href="https://www.facebook.com/kimsreng.song"><i class="bi bi-facebook"></i></a>
                    <a href="https://t.me/kimsrengsong"><i class="bi bi-telegram"></i></a>
                    <a href="https://www.instagram.com/songkimsreng?igsh=MXJlcXV4MmJ4OGJhMA=="><i
                            class="bi bi-instagram"></i></a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-wrapper fade-up delay-2">
                    <div class="hero-img-frame"> <img src="assets/images/Kimsreng Song.JPG" alt="Profile Photo"
                            class="hero-img">
                        <!-- Replace src with your actual photo -->
                        <div style="width:100%;
                                height:100%;display:flex;
                                align-items:center;
                                justify-content:center;
                                font-size:6rem;
                                color:rgba(108,99,255,0.3);">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="code-badge"><i class="bi bi-code-slash"></i></div>
                    </div>
                    <div class="hero-exp-badge">
                        <div class="num">2+</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">Years<br>Experience</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ABOUT -->
<section id="about">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left Side -->
            <div class="col-lg-6 fade-up">
                <div class="section-label mb-2">About Me</div>
                <h2 class="section-title mb-4">
                    About Me
                </h2>
                <p class="about-description mb-3">
                    I'm Kimsreng Song, a Full Stack Developer focused on shipping clean code 
                    and fluid user experiences. My core technical engine spans PHP, Laravel, and Vue.js 
                    for high-performance web development, backed by Docker for streamlined, 
                    containerized workflows. Beyond the web, I use Android Studio to build responsive, 
                    native mobile integrations that extend application functionality onto mobile devices.
                </p>
                <a href="assets/cv/SONG KIMSRENG_CV.pdf" class="btn btn-outline-accent btn-sm" download>
                    <i class="bi bi-download me-2"></i>
                    Download CV
                </a>
            </div>
            <!-- Right Side -->
            <div class="col-lg-6 fade-up delay-2">
                <div class="about-info-card">
                    <!-- Name -->
                    <div class="about-info-item">
                        <div class="about-info-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <div class="about-info-label">Name</div>
                            <div class="about-info-value">
                                Kimsreng Song
                            </div>
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="about-info-item">
                        <div class="about-info-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <div class="about-info-label">Email</div>
                            <div class="about-info-value">
                                songkimsreng001@gmail.com
                            </div>
                        </div>
                    </div>
                    <!-- Location -->
                    <div class="about-info-item">
                        <div class="about-info-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <div class="about-info-label">Location</div>
                            <div class="about-info-value">
                                Chhres, Kork Roka, Prek Pnov,
                                Phnom Penh, Cambodia
                            </div>
                        </div>
                    </div>
                    <!-- Phone -->
                    <div class="about-info-item">
                        <div class="about-info-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <div class="about-info-label">Phone</div>
                            <div class="about-info-value">
                                (+855) 87-879-728
                            </div>
                        </div>
                    </div>
                    <!-- Availability -->
                    <div class="about-info-item">
                        <div class="about-info-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="about-info-label">Availability</div>
                            <div class="about-info-value" style="color:#22c55e;">
                                Available for Work
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SKILLS -->
<section id="skills">
    <div class="container">
        <div class="text-center mb-5 fade-up">
            <div class="section-label mb-2">Academic Journey</div>
            <h2 class="section-title">Skills by Year &amp; Semester</h2>
            <p class="about-description mt-2" style="max-width:520px;margin:0 auto;">
                Technologies and subjects covered across 4 years of study, split by semester.</p>
        </div>

        <?php
        $curriculum = [
            1 => [
                1 => [
                    ['name' => 'Khmer Study I', 'icon' => 'bi-book', 'color' => '#f97316', 'tag' => 'Language'],
                    ['name' => 'Cambodia History', 'icon' => 'bi-book', 'color' => '#60a5fa', 'tag' => 'History'],
                    ['name' => 'Mathematics I', 'icon' => 'bi-calculator', 'color' => '#a78bfa', 'tag' => 'Math'],
                    ['name' => 'Basic Computer', 'icon' => 'bi-pc-display', 'color' => '#38bdf8', 'tag' => 'IT'],
                    ['name' => 'Communication', 'icon' => 'bi-chat-dots-fill', 'color' => '#f87171', 'tag' => 'Soft Skills'],
                    ['name' => 'General English I', 'icon' => 'bi-filetype-doc', 'color' => '#f472b6', 'tag' => 'Language'],
                    ['name' => 'IT Essentials', 'icon' => 'bi-cpu', 'color' => '#4ade80', 'tag' => 'IT'],
                ],
                2 => [
                    ['name' => 'Khmer Study II', 'icon' => 'bi-book', 'color' => '#fbbf24', 'tag' => 'Language'],
                    ['name' => 'Philosophy', 'icon' => 'bi-diagram-3', 'color' => '#4ade80', 'tag' => 'Soft Skills'],
                    ['name' => 'Mathematics II', 'icon' => 'bi-calculator', 'color' => '#94a3b8', 'tag' => 'Math'],
                    ['name' => 'Public Administration', 'icon' => 'bi-briefcase', 'color' => '#a78bfa', 'tag' => 'Soft Skills'],
                    ['name' => 'General English II', 'icon' => 'bi-filetype-doc', 'color' => '#f472b6', 'tag' => 'Language'],
                    ['name' => 'Programming Fundamentals I', 'icon' => 'bi-laptop', 'color' => '#38bdf8', 'tag' => 'Programming'],
                    ['name' => 'Computer Maintenance', 'icon' => 'bi-diagram-2', 'color' => '#f97316', 'tag' => 'IT'],
                    ['name' => 'Computer Image Design I', 'icon' => 'bi-image', 'color' => '#f87171', 'tag' => 'Design']
                ],
            ],
            2 => [
                1 => [
                    ['name' => 'Computer Image Design II', 'icon' => 'bi-image', 'color' => '#a78bfa', 'tag' => 'Design'],
                    ['name' => 'Programming Fundamentals II', 'icon' => 'bi-laptop', 'color' => '#38bdf8', 'tag' => 'Programming'],
                    ['name' => 'Database Application I', 'icon' => 'bi-database-fill', 'color' => '#60a5fa', 'tag' => 'Database'],
                    ['name' => 'Operating Systems', 'icon' => 'bi-windows', 'color' => '#818cf8', 'tag' => 'OS'],
                    ['name' => 'Electro Technique', 'icon' => 'bi-filetype-txt', 'color' => '#f472b6', 'tag' => 'Electronics'],
                    ['name' => 'Network Fundamentals', 'icon' => 'bi-router', 'color' => '#4ade80', 'tag' => 'Networking'],
                    ['name' => 'Data Communication', 'icon' => 'bi-arrow-left-right', 'color' => '#f97316', 'tag' => 'Networking'],
                ],
                2 => [
                    ['name' => 'Web Programming', 'icon' => 'bi-filetype-html', 'color' => '#38bdf8', 'tag' => 'Web'],
                    ['name' => 'Technical English', 'icon' => 'bi-book', 'color' => '#4ade80', 'tag' => 'Language'],
                    ['name' => 'Multimedia Editing', 'icon' => 'bi-film', 'color' => '#f472b6', 'tag' => 'Video'],
                    ['name' => 'Database Fundamentals', 'icon' => 'bi-database-fill', 'color' => '#60a5fa', 'tag' => 'Database'],
                    ['name' => 'Object-Oriented Programming', 'icon' => 'bi-code-slash', 'color' => '#f87171', 'tag' => 'Programming'],
                    ['name' => 'Camera Security I', 'icon' => 'bi-camera-video', 'color' => '#a78bfa', 'tag' => 'Security'],
                ],
            ],
            3 => [
                1 => [
                    ['name' => 'Web App Development I', 'icon' => 'bi-globe2', 'color' => '#4ade80', 'tag' => 'Web App'],
                    ['name' => 'Database Server Administration', 'icon' => 'bi-diagram-3', 'color' => '#f472b6', 'tag' => 'Database'],
                    ['name' => 'General Management', 'icon' => 'bi-briefcase', 'color' => '#f87171', 'tag' => 'Management'],
                    ['name' => 'Mobile App Development I', 'icon' => 'bi-phone', 'color' => '#f97316', 'tag' => 'Mobile'],
                    ['name' => 'System Analysis and Design', 'icon' => 'bi-diagram-2', 'color' => '#a78bfa', 'tag' => 'Analysis'],
                    ['name' => 'Vocational Consciousness', 'icon' => 'bi-brieface', 'color' => '#38bdf8', 'tag' => 'Soft skills'],
                ],
                2 => [
                    ['name' => 'Mobile App Development II', 'icon' => 'bi-phone', 'color' => '#f97316', 'tag' => 'Mobile'],
                    ['name' => 'Web Technologies', 'icon' => 'bi-box-seam', 'color' => '#4ade80', 'tag' => 'DevOps'],
                    ['name' => 'Software Engineering', 'icon' => 'bi-gear-wide-connected', 'color' => '#f472b6', 'tag' => 'Engineering'],
                    ['name' => 'Programming with Python', 'icon' => 'bi-terminal-dash', 'color' => '#a78bfa', 'tag' => 'Programming'],
                    ['name' => 'Desktop Application Development', 'icon' => 'bi-window', 'color' => '#38bdf8', 'tag' => 'Desktop'],
                    ['name' => 'Web App Development II', 'icon' => 'bi-globe2', 'color' => '#f87171', 'tag' => 'Web App'],
                ],
            ],
            4 => [
                1 => [
                    ['name' => 'None', 'icon' => 'bi-', 'color' => '#f97316', 'tag' => 'None'],
                    ['name' => 'None', 'icon' => 'bi-', 'color' => '#4ade80', 'tag' => 'None'],
                    ['name' => 'None', 'icon' => 'bi-', 'color' => '#f472b6', 'tag' => 'None'],
                    ['name' => 'None', 'icon' => 'bi-', 'color' => '#a78bfa', 'tag' => 'None'],
                ],
                2 => [
                    ['name' => 'None', 'icon' => 'bi-', 'color' => '#f97316', 'tag' => 'None'],
                    ['name' => 'None', 'icon' => 'bi-', 'color' => '#4ade80', 'tag' => 'None'],
                    ['name' => 'None', 'icon' => 'bi-', 'color' => '#f472b6', 'tag' => 'None'],
                    ['name' => 'None', 'icon' => 'bi-', 'color' => '#a78bfa', 'tag' => 'None'],
                ],
            ],
        ];
        ?>

        <!-- Tab buttons: Year 1–4 -->
        <ul class="nav skill-year-tabs justify-content-center mb-4 fade-up" id="yearTabs" role="tablist">
            <?php foreach ($curriculum as $year => $semesters): ?>
                <li class="nav-item" role="presentation">
                    <button class="skill-year-btn <?= $year === 1 ? 'active' : '' ?>" id="year<?= $year ?>-tab"
                        data-bs-toggle="tab" data-bs-target="#year<?= $year ?>" type="button" role="tab">
                        <i class="bi bi-mortarboard me-1"></i> Year <?= $year ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Tab content -->
        <div class="tab-content fade-up delay-2" id="yearTabsContent">
            <?php foreach ($curriculum as $year => $semesters): ?>
                <div class="tab-pane fade <?= $year === 1 ? 'show active' : '' ?>" id="year<?= $year ?>" role="tabpanel">

                    <div class="skill-table-card">
                        <div class="table-responsive">
                            <table class="table skill-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:130px;">#</th>
                                        <th>
                                            <span class="sem-badge sem-1">
                                                <i class="bi bi-1-circle me-1"></i>Semester 1
                                            </span>
                                        </th>
                                        <th>Category</th>
                                        <th>
                                            <span class="sem-badge sem-2">
                                                <i class="bi bi-2-circle me-1"></i>Semester 2
                                            </span>
                                        </th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sem1 = $semesters[1];
                                    $sem2 = $semesters[2];
                                    $rows = max(count($sem1), count($sem2));
                                    for ($r = 0; $r < $rows; $r++):
                                        $s1 = $sem1[$r] ?? null;
                                        $s2 = $sem2[$r] ?? null;
                                        ?>
                                        <tr>
                                            <td class="row-num">Subject <?= $r + 1 ?></td>
                                            <!-- SEM 1 -->
                                            <td>
                                                <?php if ($s1): ?>
                                                    <div class="skill-table-item">
                                                        <div class="skill-table-icon"
                                                            style="color:<?= $s1['color'] ?>; background:<?= $s1['color'] ?>22;">
                                                            <i class="bi <?= $s1['icon'] ?>"></i>
                                                        </div>
                                                        <span><?= $s1['name'] ?></span>
                                                    </div>
                                                <?php else:
                                                    echo '<span class="text-muted">—</span>'; endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($s1): ?>
                                                    <span class="tech-tag"><?= $s1['tag'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- SEM 2 -->
                                            <td>
                                                <?php if ($s2): ?>
                                                    <div class="skill-table-item">
                                                        <div class="skill-table-icon"
                                                            style="color:<?= $s2['color'] ?>; background:<?= $s2['color'] ?>22;">
                                                            <i class="bi <?= $s2['icon'] ?>"></i>
                                                        </div>
                                                        <span><?= $s2['name'] ?></span>
                                                    </div>
                                                <?php else:
                                                    echo '<span class="text-muted">—</span>'; endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($s2): ?>
                                                    <span class="tech-tag"><?= $s2['tag'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Semester summary pills -->
                        <div class="skill-table-footer">
                            <div class="sem-summary">
                                <i class="bi bi-1-circle text-accent me-1"></i>
                                <strong>Semester 1:</strong>
                                <?php foreach ($semesters[1] as $s): ?>
                                    <span class="tech-tag ms-1"><?= $s['name'] ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="sem-summary mt-2">
                                <i class="bi bi-2-circle text-accent me-1"></i>
                                <strong>Semester 2:</strong>
                                <?php foreach ($semesters[2] as $s): ?>
                                    <span class="tech-tag ms-1"><?= $s['name'] ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- PROJECTS -->
<section id="projects">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5 fade-up">
            <div>
                <div class="section-label mb-2">My Projects</div>
                <h2 class="section-title">Featured Projects</h2>
            </div>
            <a href="#" class="btn btn-outline-accent btn-sm">View All</a>
        </div>

        <div class="row g-4">
            <?php
            $projects = [
                [
                    'emoji' => '🐳',
                    'name' => 'Docker API Gateway',
                    'desc' => 'A containerized microservices API built with PHP and Laravel.',
                    'tags' => ['Laravel', 'Docker', 'Vue.js', 'Bootstrap'],
                    'delay' => 'delay-1'
                ],
                [
                    'emoji' => '💻',
                    'name' => 'Vue.js Fullstack Dashboard',
                    'desc' => 'An interactive real-time management dashboard with a Laravel backend.',
                    'tags' => ['Laravel', 'PHP', 'XAMPP', 'Tailwind CSS'],
                    'delay' => 'delay-2'
                ],
                [
                    'emoji' => '📱',
                    'name' => 'Android Companion App',
                    'desc' => 'A native mobile application that syncs seamlessly with a Laravel API.',
                    'tags' => ['Android Studio', 'Laravel', 'API'],
                    'delay' => 'delay-3'
                ],
            ];
            ?>

            <?php foreach ($projects as $p): ?>
            <div class="col-md-4 fade-up <?= $p['delay'] ?>">
                <div class="project-card">
                    <div class="project-thumb">
                        <?= $p['emoji'] ?>
                        <a href="#" class="project-link-btn"><i class="bi bi-arrow-up-right"></i></a>
                    </div>
                    <div class="project-body">
                        <h5><?= htmlspecialchars($p['name']) ?></h5>
                        <p><?= htmlspecialchars($p['desc']) ?></p>
                        <div>
                            <?php foreach ($p['tags'] as $tag): ?>
                            <span class="tech-tag"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- CONTACT -->
<section id="contact">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5 fade-up">
                    <div class="section-label mb-2">Contact Me</div>
                    <h2 class="section-title">Get In Touch</h2>
                    <p class="text-muted">Have a project in mind or want to work together? Feel free to send me a
                        message.</p>
                </div>
                <div class="contact-form-card fade-up delay-2">
                    <?php $authUser = getCurrentUser(); ?>
                    <?php if ($authUser): ?>
                        <!-- Logged In User Form -->
                        <div class="contact-user-badge d-flex align-items-center justify-content-between mb-4 p-3 rounded-3"
                            style="background:var(--accent-light);border:1px solid var(--border);">
                            <div class="d-flex align-items-center gap-3">
                                <div class="profile-avatar" style="width:40px;height:40px;">
                                    <?php if (!empty($authUser['avatar'])): ?>
                                        <img src="<?= htmlspecialchars($authUser['avatar']) ?>"
                                            alt="<?= htmlspecialchars($authUser['name']) ?>" class="profile-avatar-img"
                                            referrerpolicy="no-referrer">
                                    <?php else: ?>
                                        <span><?= strtoupper(substr($authUser['name'] ?: 'U', 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:0.95rem;color:var(--text-primary);">
                                        Sending as: <?= htmlspecialchars($authUser['name']) ?>
                                    </div>
                                    <div style="font-size:0.8rem;color:var(--text-muted);">
                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($authUser['email']) ?>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill"><i
                                    class="bi bi-shield-check me-1"></i>Verified</span>
                        </div>

                        <form id="contactForm">
                            <?= csrfField() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Your Name</label>
                                    <input type="text" name="name" class="form-control"
                                        value="<?= htmlspecialchars($authUser['name']) ?>" readonly
                                        style="opacity:0.85;cursor:not-allowed;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Your Email (Replies will be sent here)</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?= htmlspecialchars($authUser['email']) ?>" readonly
                                        style="opacity:0.85;cursor:not-allowed;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Subject</label>
                                    <input type="text" name="subject" class="form-control"
                                        placeholder="e.g. Full-Stack Web Development Project Inquiry" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Your Message</label>
                                    <textarea name="message" class="form-control" rows="5"
                                        placeholder="Tell me about your project requirements, timeline, or question..."
                                        required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-accent w-100" id="sendMsgBtn">
                                        <i class="bi bi-send me-2"></i>Send Message to Admin
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Guest / Locked State Prompt -->
                        <div class="contact-auth-locked text-center py-4 px-3">
                            <div class="contact-lock-icon mb-3 mx-auto"
                                style="width:64px;height:64px;border-radius:50%;background:var(--accent-light);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:1.8rem;border:1px solid var(--border);">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                            <h4 class="fw-bold mb-2">Account Required to Contact Admin</h4>
                            <p class="text-muted mx-auto mb-4" style="max-width:480px;font-size:0.95rem;line-height:1.6;">
                                To ensure secure communication and direct response tracking, please log in or register
                                before sending a message to Admin.
                            </p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <a href="login.php" class="btn btn-accent px-4 py-2">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to Contact
                                </a>
                                <a href="register.php" class="btn btn-outline-accent px-4 py-2">
                                    <i class="bi bi-person-plus me-2"></i>Create Account
                                </a>
                            </div>
                            <div class="mt-4 pt-3 border-top" style="border-color:var(--border-subtle)!important;">
                                <span style="font-size:0.85rem;color:var(--text-muted);">
                                    <i class="bi bi-envelope-at me-1"></i>Admin Email: <a
                                        href="mailto:songkimsreng001@gmail.com"
                                        class="text-accent text-decoration-none fw-semibold">songkimsreng001@gmail.com</a>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
require_once 'includes/footer.php';