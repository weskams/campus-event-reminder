<?php defined('APP_ROOT') || exit; ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <a class="brand" href="index.php">
                    <?= brand_mark() ?>
                    <span class="brand-text">
                        <span class="brand-name">Kabarak</span>
                        <span class="brand-sub">Campus Events</span>
                    </span>
                </a>
                <p>Every talk, match and celebration on campus, with a reminder before it starts.</p>
            </div>

            <nav class="footer-links" aria-label="Footer">
                <div>
                    <h2>Explore</h2>
                    <ul>
                        <li><a href="index.php">All events</a></li>
                        <li><a href="upcoming-soon.php">Next 24 hours</a></li>
                        <?php foreach (EVENT_CATEGORIES as $footerCategory): ?>
                            <li><a href="index.php?category=<?= e(urlencode($footerCategory)) ?>"><?= e($footerCategory) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h2>Students</h2>
                    <ul>
                        <li><a href="student-login.php">Log in</a></li>
                        <li><a href="student-register.php">Create an account</a></li>
                        <li><a href="student-dashboard.php">My dashboard</a></li>
                    </ul>
                </div>
                <div>
                    <h2>Organisers</h2>
                    <ul>
                        <li><a href="login.php">Organiser login</a></li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="container footer-bottom">
            <p>&copy; <?= date('Y') ?> Kabarak University</p>
            <p>Made for campus life, by students.</p>
        </div>
    </footer>
</body>
</html>
