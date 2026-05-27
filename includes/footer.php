<?php
/**
 * Shared page footer for authenticated views.
 * Closes the <main> opened in header.php, then renders the footer
 * bar and closes the document.
 */
?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <small>
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot;
                MIT122 Interactive Web Design and Development project
            </small>
        </div>
    </footer>
</body>
</html>
